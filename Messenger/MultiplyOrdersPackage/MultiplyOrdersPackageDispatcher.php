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

namespace BaksDev\Orders\Order\Messenger\MultiplyOrdersPackage;


use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPackage;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Stocks\Messenger\Orders\MultiplyProductStocksPackage\MultiplyProductStocksPackageMessage;
use BaksDev\Users\User\Repository\UserTokenStorage\UserTokenStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Метод меняет статус заказа на Package «Упаковка заказов» */
#[AsMessageHandler(priority: 100)]
final readonly class MultiplyOrdersPackageDispatcher
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private CurrentOrderEventInterface $CurrentOrderEventRepository,
        private DeduplicatorInterface $deduplicator,
        private ExistOrderEventByStatusInterface $ExistOrderEventByStatusRepository,
        private CentrifugoPublishInterface $publish,
        private OrderStatusHandler $OrderStatusHandler,
        private MessageDispatchInterface $messageDispatch,
        private UserTokenStorageInterface $UserTokenStorage
    ) {}

    public function __invoke(MultiplyOrdersPackageMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                (string) $message->getOrderId(),
                self::class,
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $OrderEvent = $this->CurrentOrderEventRepository
            ->forOrder($message->getOrderId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->logger->critical(
                sprintf('orders-order: Ошибка при получении информации о заказе при упаковке %s', $message->getOrderId()),
                [self::class],
            );

            return;
        }

        /** Делаем проверку, что статус применяется впервые  */

        $isOtherExists = $this->ExistOrderEventByStatusRepository
            ->forOrder($OrderEvent->getMain())
            ->excludeOrderEvent($OrderEvent->getId())
            ->forStatus(OrderStatusPackage::class)
            ->isOtherExists();

        if(true === $isOtherExists)
        {
            $Deduplicator->save();
            return;
        }

        /** Скрываем заказ у всех пользователей */
        $this->publish
            ->addData(['order' => (string) $message->getOrderId()])
            ->send('orders');

        /** Авторизуем текущего пользователя для лога изменений если сообщение обрабатывается из очереди */
        if(false === $this->UserTokenStorage->isUser())
        {
            $this->UserTokenStorage->authorization($message->getCurrent());
        }

        /**
         * Обновляем статус заказа и присваиваем профиль склада упаковки.
         */
        $OrderStatusDTO = new OrderStatusDTO(OrderStatusPackage::class, $OrderEvent->getId());
        $OrderStatusDTO->setProfile($message->getUserProfile())->setComment($OrderEvent->getComment());

        $Order = $this->OrderStatusHandler->handle($OrderStatusDTO);

        if(false === ($Order instanceof Order))
        {
            $this->logger->critical(
                sprintf('orders-order: Ошибка %s при обновлении статуса заказа на упаковке', $Order),
                [self::class.':'.__LINE__, var_export($message, true)],
            );

            return;
        }

        $Deduplicator->save();

        if(class_exists(MultiplyProductStocksPackageMessage::class))
        {
            $MultiplyProductStocksPackageMessage = new MultiplyProductStocksPackageMessage(
                $message->getOrderId(),
                $message->getUserProfile(),
            );

            $this->messageDispatch->dispatch(
                message: $MultiplyProductStocksPackageMessage,
                transport: 'products-stocks',
            );
        }

    }
}
