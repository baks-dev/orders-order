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

namespace BaksDev\Orders\Order\Messenger\ProductReserveByOrderNew;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Messenger\ProductsReserveByOrderCancel\ProductsReserveByOrderCancelMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusDecommission;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileLogisticWarehouse\UserProfileLogisticWarehouseInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Создаем резерв продукции при поступлении нового заказа
 * @note Работа с резервами в карточке - самый высокий приоритет
 */
#[AsMessageHandler(priority: 999)]
final readonly class ProductReserveByOrderNewDispatcher
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private OrderEventInterface $orderEventRepository,
        private CurrentOrderEventInterface $CurrentOrderEvent,
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
                self::class
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $OrderEvent = $this->orderEventRepository
            ->find($message->getEvent());

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->logger->critical(
                'products-sign: Не найдено событие OrderEvent',
                [self::class.':'.__LINE__, var_export($message, true)]
            );

            return;
        }


        /** Если заказ не является новым и не списывает продукцию на складе - завершаем обработчик */
        if(
            false === $OrderEvent->isStatusEquals(OrderStatusNew::class)
            && false === $OrderEvent->isStatusEquals(OrderStatusDecommission::class)
        )
        {
            return;
        }


        /** Получаем активное событие заказа в случае если статус заказа изменился */
        if(empty($OrderEvent->getOrderNumber()))
        {
            $OrderEvent = $this->CurrentOrderEvent
                ->forOrder($message->getId())
                ->find();

            if(false === ($OrderEvent instanceof OrderEvent))
            {
                $this->logger->critical(
                    'orders-order: Не найдено событие OrderEvent',
                    [self::class.':'.__LINE__, var_export($message, true)]
                );

                return;
            }
        }

        $UserProfileUid = $OrderEvent->getOrderProfile();

        if(false === ($UserProfileUid instanceof UserProfileUid))
        {
            return;
        }


        /** Проверяем, является ли данный профиль логистическим складом */
        $isLogisticWarehouse = $this->UserProfileLogisticWarehouseRepository
            ->forProfile($UserProfileUid)
            ->isLogisticWarehouse();


        /**
         * Новый заказ не имеет предыдущего события!!! (за исключением случая, когда мы намеренно создали новый заказ
         * после отправки заказа из другого профиля на упаковку)
         */
        if(false !== ($message->getLast() instanceof OrderEventUid))
        {
            if(false === ($message->getLastProfile() instanceof UserProfileUid))
            {
                return;
            }


            /** Если это не первое событие заказа и профиль его не менялся, не меняем резерв */
            if($UserProfileUid->equals($message->getLastProfile()))
            {
                return;
            }


            /** Проверяем, являлся ли данный профиль логистическим складом в прошлом событии */
            $wasLogisticWarehouse = $this->UserProfileLogisticWarehouseRepository
                ->forProfile($message->getLastProfile())
                ->isLogisticWarehouse();


            /** Если оба склада логистические - не меняем резерв */
            if(true === $isLogisticWarehouse && true === $wasLogisticWarehouse)
            {
                return;
            }


            /** Если профиль из прошлого события являлся логистическим складом, а текущий - нет, то снимаем резерв */
            if(true === $wasLogisticWarehouse && false === $isLogisticWarehouse)
            {
                $this->logger->info(
                    sprintf(
                        '%s: Снимаем резерв продукции в карточке для нового заказа (см. products-product.log)',
                        $OrderEvent->getOrderNumber()
                    ),
                    [
                        'status' => OrderStatusNew::STATUS,
                        'deduplicator' => $Deduplicator->getKey()
                    ]
                );

                $EditOrderDTO = new EditOrderDTO();
                $OrderEvent->getDto($EditOrderDTO);

                /** @var OrderProductDTO $product */
                foreach($EditOrderDTO->getProduct() as $product)
                {
                    /** Устанавливаем новый резерв продукции в заказе */
                    $this->messageDispatch->dispatch(
                        new ProductsReserveByOrderCancelMessage(
                            $product->getProduct(),
                            $product->getOffer(),
                            $product->getVariation(),
                            $product->getModification(),
                            $product->getPrice()->getTotal()
                        )
                    );
                }

                $Deduplicator->save();

                return;
            }
        }

        if(false === $isLogisticWarehouse)
        {
            return;
        }

        $this->logger->info(
            sprintf(
                '%s: Добавляем резерв продукции в карточке для нового заказа (см. products-product.log)',
                $OrderEvent->getOrderNumber()
            ),
            [
                'status' => OrderStatusNew::STATUS,
                'deduplicator' => $Deduplicator->getKey()
            ]
        );

        $EditOrderDTO = new EditOrderDTO();
        $OrderEvent->getDto($EditOrderDTO);

        /** @var OrderProductDTO $product */
        foreach($EditOrderDTO->getProduct() as $product)
        {
            /** Устанавливаем новый резерв продукции в заказе */
            $this->messageDispatch->dispatch(
                new ProductReserveByOrderNewMessage(
                    $product->getProduct(),
                    $product->getOffer(),
                    $product->getVariation(),
                    $product->getModification(),
                    $product->getPrice()->getTotal()
                )
            );
        }

        $Deduplicator->save();
    }

}
