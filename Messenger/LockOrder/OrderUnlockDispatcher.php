<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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
 *
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Messenger\LockOrder;

use BaksDev\Centrifugo\BaksDevCentrifugoBundle;
use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Lock\OrderLock;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPhone;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusUnpaid;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Разблокирует заказ, слушая общие сообщения
 *
 * @note используется в конце обработки асинхронных сообщений
 */
#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: -999)]
final readonly class OrderUnlockDispatcher
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private CurrentOrderEventInterface $currentOrderEventRepository,
        private MessageDispatchInterface $dispatch,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        /**
         * Получаем активное событие заказа
         */

        $OrderEvent = $this->currentOrderEventRepository
            ->forOrder($message->getId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->logger->critical(
                message: 'Не найдено активное событие OrderEvent',
                context: [
                    self::class.':'.__LINE__,
                    var_export($message, true),
                ],
            );

            return;
        }

        /** Если нет связи с блокировкой - прерываем обработчик */
        if(false === ($OrderEvent->getLock() instanceof OrderLock))
        {
            return;
        }

        /** Если заказ уже РАЗБЛОКИРОВАН - прерываем обработчик */
        if(false === $OrderEvent->getLock()->getValue())
        {
            return;
        }

        /**
         *   Всегда разблокируем статусы:
         *   - Unpaid «В ожидании оплаты»
         *   - Phone «Не дозвонились»
         *
         */
        if(
            true === $OrderEvent->getStatus()->equals(OrderStatusUnpaid::class)
            || true === $OrderEvent->getStatus()->equals(OrderStatusPhone::class)
        )
        {

            /** @see OrderUnlockHandler */
            $OrderUnlockMessage = new OrderUnlockMessage($message->getId());

            $this->dispatch->dispatch(
                message: $OrderUnlockMessage,
                transport: 'orders-order',
            );

            return;
        }

        /**
         * Если установлен модуль products-stocks - заказ ДОЛЖЕН быть разблокирован по результатам обработки складской заявки
         */
        if(true === class_exists(BaksDevProductsStocksBundle::class))
        {
            return;
        }

        /** @see OrderUnlockHandler */
        $OrderUnlockMessage = new OrderUnlockMessage($message->getId());

        $this->dispatch->dispatch(
            message: $OrderUnlockMessage,
            transport: 'orders-order',
        );
    }

}
