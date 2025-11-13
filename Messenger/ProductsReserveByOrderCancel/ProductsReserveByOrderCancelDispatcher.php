<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Messenger\ProductsReserveByOrderCancel;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusDecommission;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusReturn;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByEventInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierResult;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileLogisticWarehouse\UserProfileLogisticWarehouseInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Снимаем только резерв с карточки товара при отмене заказа
 *
 * @note Работа с резервами в карточке - самый высокий приоритет
 */
#[AsMessageHandler(priority: 999)]
final readonly class ProductsReserveByOrderCancelDispatcher
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private OrderEventInterface $OrderEventRepository,
        private CurrentOrderEventInterface $CurrentOrderEvent,
        private CurrentProductIdentifierByEventInterface $CurrentProductIdentifierRepository,
        private UserProfileLogisticWarehouseInterface $UserProfileLogisticWarehouseRepository,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $messageDispatch,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                (string) $message->getId(),
                self::class,
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $OrderEvent = $this->OrderEventRepository
            ->find($message->getEvent());

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->logger->critical(
                'products-sign: Не найдено событие OrderEvent',
                [self::class.':'.__LINE__, var_export($message, true)],
            );

            return;
        }

        /**
         * Не снимаем резерв в карточке, если статус не:
         * - Canceled «Отменен»
         * - Decommission «Списание»
         * - Return «Возврат»
         */
        if(
            false === $OrderEvent->isStatusEquals(OrderStatusCanceled::class)
            && false === $OrderEvent->isStatusEquals(OrderStatusDecommission::class)
            && false === $OrderEvent->isStatusEquals(OrderStatusReturn::class)
        )
        {
            return;
        }

        /**  Получаем предыдущее событие заказа если статус текущего - Canceled «Отменен»  */
        if(true === $OrderEvent->isStatusEquals(OrderStatusCanceled::class))
        {
            $LastOrderEvent = $this->OrderEventRepository
                ->find($message->getLast());

            if(false === ($LastOrderEvent instanceof OrderEvent))
            {
                $this->logger->critical(
                    'products-sign: Не найдено предыдущее событие OrderEvent',
                    [self::class.':'.__LINE__, var_export($message, true)],
                );

                return;
            }

            /**
             * Не снимаем резерв в карточке, если предыдущее событие заказа Completed «Выполнен».
             * Резерв был списан при завершении
             */
            if($LastOrderEvent->isStatusEquals(OrderStatusCompleted::class))
            {
                return;
            }
        }

        /** Получаем активное событие заказа для номера и профиля склада на случай, если статус заказа изменился */
        if(empty($OrderEvent->getOrderNumber()))
        {
            $OrderEvent = $this->CurrentOrderEvent
                ->forOrder($message->getId())
                ->find();

            if(false === ($OrderEvent instanceof OrderEvent))
            {
                $this->logger->critical(
                    'orders-order: Не найдено событие OrderEvent',
                    [self::class.':'.__LINE__, var_export($message, true)],
                );

                return;
            }
        }

        /**
         * Проверяем, является ли данный профиль логистическим складом
         */

        $UserProfileUid = $OrderEvent->getOrderProfile();

        if(false === ($UserProfileUid instanceof UserProfileUid))
        {
            return;
        }

        $isLogisticWarehouse = $this->UserProfileLogisticWarehouseRepository
            ->forProfile($UserProfileUid)
            ->isLogisticWarehouse();

        if(false === $isLogisticWarehouse)
        {
            return;
        }

        $this->logger->info(
            sprintf(
                '%s: Снимаем общий резерв в карточке товара при отмене заказа (см. products-product.log)',
                $OrderEvent->getOrderNumber(),
            ),
            [
                'status' => (string) $OrderEvent->getStatus(),
                'deduplicator' => $Deduplicator->getKey(),
            ],
        );

        $EditOrderDTO = new EditOrderDTO();
        $OrderEvent->getDto($EditOrderDTO);

        /** @var OrderProductDTO $product */
        foreach($EditOrderDTO->getProduct() as $product)
        {
            /** Получаем активные идентификаторы карточки на случай, если товар обновлялся */

            $CurrentProductIdentifier = $this->CurrentProductIdentifierRepository
                ->forEvent($product->getProduct())
                ->forOffer($product->getOffer())
                ->forVariation($product->getVariation())
                ->forModification($product->getModification())
                ->find();

            if(false === ($CurrentProductIdentifier instanceof CurrentProductIdentifierResult))
            {
                $this->logger->critical(
                    'products-sign: Продукт не найден',
                    [
                        'product' => (string) $product->getProduct(),
                        'offer' => (string) $product->getOffer(),
                        'variation' => (string) $product->getVariation(),
                        'modification' => (string) $product->getModification(),
                        self::class.':'.__LINE__,
                    ],
                );

                continue;
            }

            /** Снимаем резерв с карточки отмененного заказа */

            $this->messageDispatch->dispatch(
                new ProductsReserveByOrderCancelMessage(
                    $CurrentProductIdentifier->getEvent(),
                    $CurrentProductIdentifier->getOffer(),
                    $CurrentProductIdentifier->getVariation(),
                    $CurrentProductIdentifier->getModification(),
                    $product->getPrice()->getTotal(),
                ),
                transport: 'products-product',
            );
        }

        $Deduplicator->save();
    }
}
