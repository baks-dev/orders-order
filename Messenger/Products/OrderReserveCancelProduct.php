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
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
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
final class OrderReserveCancelProduct
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CurrentQuantityByModificationInterface $quantityByModification,
        private readonly CurrentQuantityByVariationInterface $quantityByVariation,
        private readonly CurrentQuantityByOfferInterface $quantityByOffer,
        private readonly CurrentQuantityByEventInterface $quantityByEvent,
        private readonly ExistOrderEventByStatusInterface $existOrderEventByStatus,
        private readonly DeduplicatorInterface $deduplicator,
        LoggerInterface $ordersOrderLogger,
    ) {

        $this->logger = $ordersOrderLogger;

    }


    /** Снимаем резерв с продукции при отмене заказа  */
    public function __invoke(OrderMessage $message): void
    {

        $Deduplicator = $this->deduplicator
            ->namespace(md5(self::class))
            ->deduplication([
                (string) $message->getId(),
                OrderStatusCanceled::STATUS
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $this->entityManager->clear();

        $OrderEvent = $this->entityManager
            ->getRepository(OrderEvent::class)
            ->find($message->getEvent());

        if(!$OrderEvent)
        {
            return;
        }

        /** Если статус не "ОТМЕНА" - завершаем обработчик */
        if(false === $OrderEvent->getStatus()->equals(OrderStatusCanceled::class))
        {
            return;
        }

        /** Не снимаем резерв с продукта если дублируется событие */
        $isOtherExists = $this->existOrderEventByStatus->isOtherExists(
            $message->getId(),
            $message->getEvent(),
            OrderStatusCanceled::class
        );

        if($isOtherExists)
        {
            return;
        }


        if($message->getLast())
        {
            /**
             * Предыдущее событие заказа
             *
             * @var OrderEvent $OrderEventLast
             */
            $OrderEventLast = $this->entityManager->getRepository(OrderEvent::class)->find($message->getLast());

            /** Если статус предыдущего события "ВЫПОЛНЕН" - не снимаем резерв или наличие (просто удаляем)  */
            if($OrderEventLast->getStatus()->equals(OrderStatusCompleted::class))
            {
                return;
            }
        }

        $Deduplicator->save();

        $this->logger->info('Снимаем общий резерв продукции в карточке при отмене заказа:');

        /** @var OrderProduct $product */
        foreach($OrderEvent->getProduct() as $product)
        {
            /** Снимаем резерв отмененного заказа */
            $this->changeReserve($product);
        }
    }

    /**
     * Метод снимает резерв с продукции отмененного заказа
     */
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

        if($Quantity && $Quantity->subReserve($product->getTotal()))
        {
            $this->entityManager->flush();

            $this->logger->info(
                'Сняли общий резерв продукции в карточке при отмене заказа',
                [
                    self::class.':'.__LINE__,
                    'total' => $product->getTotal(),
                    'product' => (string) $product->getProduct(),
                    'offer' => (string) $product->getOffer(),
                    'variation' => (string) $product->getVariation(),
                    'modification' => (string) $product->getModification(),
                ]
            );

            return;
        }

        $this->logger->critical(
            'Невозможно снять резерв с карточки товара при отмене заказа: карточка не найдена либо недостаточное количество в резерве)',
            [
                self::class.':'.__LINE__,
                'total' => (string) $product->getTotal(),
                'product' => (string) $product->getProduct(),
                'offer' => (string) $product->getOffer(),
                'variation' => (string) $product->getVariation(),
                'modification' => (string) $product->getModification(),
            ]
        );

    }
}
