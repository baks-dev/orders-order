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
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Repository\CurrentQuantity\CurrentQuantityByEventInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Modification\CurrentQuantityByModificationInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Offer\CurrentQuantityByOfferInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Variation\CurrentQuantityByVariationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Ставит продукцию в резерв в карточке товара
 * @note Имеет самый высокий приоритет
 */
#[AsMessageHandler(priority: 100)]
final class OrderReserveProduct
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CurrentQuantityByModificationInterface $quantityByModification,
        private readonly CurrentQuantityByVariationInterface $quantityByVariation,
        private readonly CurrentQuantityByOfferInterface $quantityByOffer,
        private readonly CurrentQuantityByEventInterface $quantityByEvent,
        private readonly CurrentOrderEventInterface $currentOrderEvent,
        private readonly DeduplicatorInterface $deduplicator,
        LoggerInterface $ordersOrderLogger,
    ) {

        $this->logger = $ordersOrderLogger;
    }


    /**
     * Сообщение ставит продукцию в резерв в карточке товара
     */
    public function __invoke(OrderMessage $message): void
    {

        $Deduplicator = $this->deduplicator
            ->namespace(md5(self::class))
            ->deduplication([
                $message->getId(),
                OrderStatusNew::STATUS
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** Новый заказ не имеет предыдущего события */
        if($message->getLast())
        {
            return;
        }

        $this->entityManager->clear();
        $OrderEvent = $this->currentOrderEvent
            ->order($message->getId())
            ->getCurrentOrderEvent();

        if(!$OrderEvent)
        {
            return;
        }

        /** Если заказ не является новым - завершаем обработчик */
        if(false === $OrderEvent->getStatus()->equals(OrderStatusNew::class))
        {
            return;
        }

        $Deduplicator->save();

        /** @var OrderProduct $product */
        foreach($OrderEvent->getProduct() as $product)
        {

            $this->logger->info(
                'Добавляем общий резерв продукции в карточке',
                [
                    __FILE__.':'.__LINE__,
                    'total' => $product->getTotal(),
                    'ProductEventUid' => (string) $product->getProduct(),
                    'ProductOfferUid' => (string) $product->getOffer(),
                    'ProductVariationUid' => (string) $product->getVariation(),
                    'ProductModificationUid' => (string) $product->getModification(),
                ]
            );

            /** Устанавливаем новый резерв продукции в заказе */
            $this->handle($product);
        }
    }

    public function handle(OrderProduct $product): void
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

        if($Quantity && $Quantity->addReserve($product->getTotal()))
        {
            $this->entityManager->flush();
            return;
        }

        $this->logger->critical(
            'Невозможно добавить резерв на новый заказ: карточка не найдена либо недостаточное количество для резерва)',
            [
                __FILE__.':'.__LINE__,
                'total' => (string) $product->getTotal(),
                'ProductEventUid' => (string) $product->getProduct(),
                'ProductOfferUid' => (string) $product->getOffer(),
                'ProductVariationUid' => (string) $product->getVariation(),
                'ProductModificationUid' => (string) $product->getModification(),
            ]
        );

    }
}
