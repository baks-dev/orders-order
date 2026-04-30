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
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Lock\OrderLock;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\UseCase\Admin\Lock\OrderLockDTO;
use BaksDev\Orders\Order\UseCase\Admin\Lock\OrderLockHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Разблокирует заказ, слушая отдельное сообщение о разблокировке
 *
 * @note используется там, где нам необходимо синхронно разблокировать зака
 */
#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: 0)]
final readonly class OrderUnlockHandler
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private CurrentOrderEventInterface $currentOrderEventRepository,
        private OrderLockHandler $orderLockHandler,
        private ?CentrifugoPublishInterface $centrifugoPublish = null,
    ) {}

    public function __invoke(OrderUnlockMessage $message): void
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
        if(false === $OrderEvent->getLock()->isLock())
        {
            $this->logger->warning(
                message: sprintf('%s: заказ => УЖЕ РАЗБЛОКИРОВАН в статусе %s',
                    $OrderEvent->getPostingNumber(),
                    $OrderEvent->getStatus()->getOrderStatusValue(),
                ),
                context: [self::class.':'.__LINE__, $message->getContext()],
            );

            return;
        }

        $OrderLockDTO = new OrderLockDTO(
            $OrderEvent->getId(),
            $OrderEvent->getStatus()
        );

        $OrderEvent->getLock()->getDto($OrderLockDTO);

        $OrderLockDTO->unlock(); // снимаем блокировку

        $OrderLock = $this->orderLockHandler->handle($OrderLockDTO);

        if(false === ($OrderLock instanceof OrderLock))
        {
            $this->logger->critical(
                message: sprintf('%s: Ошибка при снятии блокировки с заказа',
                    $OrderEvent->getPostingNumber(),
                ),
                context: [self::class.':'.__LINE__],
            );
        }

        $this->logger->info(
            message: sprintf('%s: заказ => РАЗБЛОКИРОВАЛИ заказ в статусе %s',
                $OrderEvent->getPostingNumber(),
                $OrderEvent->getStatus()->getOrderStatusValue(),
            ),
            context: [self::class.':'.__LINE__, $message->getContext()],
        );


        if(true === class_exists(BaksDevCentrifugoBundle::class))
        {
            /**
             * Разблокируем перемещение заказа в канбане
             */

            $socket = $this->centrifugoPublish
                ->addData([
                    'order' => (string) $OrderEvent->getMain(), // для поиска карточки в канбане
                    'number' => (string) $OrderEvent->getPostingNumber() ?? $OrderEvent->getOrderNumber(), // номер заказа
                    'lock' => false, // разблокировка перетаскивания карточки на UI
                    'context' => self::class.':'.__LINE__,
                ])
                ->send('orders'); // канал для обработки заказа

            if($socket && $socket->isError())
            {
                $this->logger->critical(
                    message: 'orders-order: Ошибка при отправке информации о блокировке в Centrifugo',
                    context: [
                        $socket->getMessage(),
                        'number' => $OrderEvent->getPostingNumber(),
                        'main' => $OrderEvent->getMain(),
                        self::class.':'.__LINE__,
                    ],
                );
            }
        }
    }
}
