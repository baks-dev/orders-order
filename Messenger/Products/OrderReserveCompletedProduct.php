<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Messenger\Products;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Lock\AppLockInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCompleted;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Repository\CurrentQuantity\CurrentQuantityByEventInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Modification\CurrentQuantityByModificationInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Offer\CurrentQuantityByOfferInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Variation\CurrentQuantityByVariationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class OrderReserveCompletedProduct
{
    private EntityManagerInterface $entityManager;
    private CurrentQuantityByModificationInterface $quantityByModification;
    private CurrentQuantityByVariationInterface $quantityByVariation;
    private CurrentQuantityByOfferInterface $quantityByOffer;
    private CurrentQuantityByEventInterface $quantityByEvent;
    private LoggerInterface $logger;
    private DeduplicatorInterface $deduplicator;


    public function __construct(
        EntityManagerInterface $entityManager,
        CurrentQuantityByModificationInterface $quantityByModification,
        CurrentQuantityByVariationInterface $quantityByVariation,
        CurrentQuantityByOfferInterface $quantityByOffer,
        CurrentQuantityByEventInterface $quantityByEvent,
        LoggerInterface $ordersOrderLogger,
        DeduplicatorInterface $deduplicator
    ) {
        $this->entityManager = $entityManager;
        $this->entityManager->clear();

        $this->quantityByModification = $quantityByModification;
        $this->quantityByVariation = $quantityByVariation;
        $this->quantityByOffer = $quantityByOffer;
        $this->quantityByEvent = $quantityByEvent;
        $this->logger = $ordersOrderLogger;

        $this->deduplicator = $deduplicator;
    }


    /**
     * Снимаем резерв и наличие с продукта если заказ выполнен
     */
    public function __invoke(OrderMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->deduplication([
                $message->getId(),
                OrderStatusCompleted::STATUS
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $OrderEvent = $this->entityManager->getRepository(OrderEvent::class)->find($message->getEvent());

        if(!$OrderEvent)
        {
            return;
        }

        /** Если статус не Completed «Выполнен» - завершаем обработчик */
        if(false === $OrderEvent->getStatus()->equals(OrderStatusCompleted::class))
        {
            return;
        }

        /** @var OrderProduct $product */
        foreach($OrderEvent->getProduct() as $product)
        {
            $this->logger->info(
                'Снимаем общий резерв и наличие продукции в карточке при выполненном заказе',
                [
                    __FILE__.':'.__LINE__,
                    'total' => (string) $product->getTotal(),
                    'ProductEventUid' => (string) $product->getProduct(),
                    'ProductOfferUid' => (string) $product->getOffer(),
                    'ProductVariationUid' => (string) $product->getVariation(),
                    'ProductModificationUid' => (string) $product->getModification(),
                ]
            );

            /** Снимаем резерв выполненного заказа */
            $this->changeReserve($product);

        }

        $Deduplicator->save();
    }


    public function changeReserve(OrderProduct $product): void
    {
        $Quantity = null;

        /** Обновляем резерв модификации множественного варианта торгового предложения */
        if(!$Quantity && $product->getModification())
        {
            /** @var ProductModificationQuantity $Quantity */
            $Quantity = $this->quantityByModification->getModificationQuantity(
                $product->getProduct(),
                $product->getOffer(),
                $product->getVariation(),
                $product->getModification()
            );
        }

        /** Обновляем резерв множественного варианта торгового предложения */
        if(!$Quantity && $product->getVariation())
        {
            /** @var ProductVariationQuantity $Quantity */
            $Quantity = $this->quantityByVariation->getVariationQuantity(
                $product->getProduct(),
                $product->getOffer(),
                $product->getVariation()
            );
        }

        /** Обновляем резерв торгового предложения */
        if(!$Quantity && $product->getOffer())
        {
            $Quantity = $this->quantityByOffer->getOfferQuantity(
                $product->getProduct(),
                $product->getOffer(),
            );
        }

        /** Обновляем резерв продукта */
        if(!$Quantity && $product->getProduct())
        {
            $Quantity = $this->quantityByEvent->getQuantity(
                $product->getProduct()
            );
        }

        /* Снимаем резерв и наличие */

        if($Quantity && $Quantity->subReserve($product->getTotal()) && $Quantity->subQuantity($product->getTotal()))
        {
            $this->entityManager->flush();
            return;
        }

        $this->logger->critical(
            'Невозможно снять резерв и наличие с карточки товара выпаленного заказа: карточка не найдена либо недостаточное количество в резерве)',
            [
                __FILE__.':'.__LINE__,
                'total' => $product->getTotal(),
                'product' => (string) $product->getProduct(),
                'offer' => (string) $product->getOffer(),
                'variation' => (string) $product->getVariation(),
                'modification' => (string) $product->getModification(),

            ]
        );
    }

}
