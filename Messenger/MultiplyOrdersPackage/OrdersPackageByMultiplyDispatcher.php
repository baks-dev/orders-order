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

namespace BaksDev\Orders\Order\Messenger\MultiplyOrdersPackage;

use BaksDev\Centrifugo\BaksDevCentrifugoBundle;
use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Messenger\LockOrder\OrderUnlockMessage;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPackage;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Users\User\Repository\UserTokenStorage\UserTokenStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Метод меняет статус заказа на Package «Упаковка заказов»
 *
 * @note Снимает блокировку с заказа
 */
#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: 100)]
final readonly class OrdersPackageByMultiplyDispatcher
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private MessageDispatchInterface $messageDispatch,
        private UserTokenStorageInterface $UserTokenStorageRepository,
        private OrderStatusHandler $OrderStatusHandler,
        private ?CentrifugoPublishInterface $centrifugoPublish = null,
    ) {}

    public function __invoke(OrdersPackageByMultiplyMessage $message): void
    {

        /**
         * Обновляем статус заказа и присваиваем профиль склада упаковки.
         */
        $OrderStatusDTO = new OrderStatusDTO(OrderStatusPackage::class, $message->getOrderEvent());
        $OrderStatusDTO
            ->setProfile($message->getUserProfile())
            ->setComment($message->getComment());


        /** Авторизуем текущего пользователя для лога изменений если сообщение обрабатывается из очереди */
        if(false === $this->UserTokenStorageRepository->isUser())
        {
            $this->UserTokenStorageRepository->authorization($message->getCurrentUser());
        }

        $Order = $this->OrderStatusHandler->handle($OrderStatusDTO);

        if(false === ($Order instanceof Order))
        {
            $this->logger->critical(
                sprintf('orders-order: Ошибка %s при обновлении статуса заказа на упаковке',
                    $Order,
                ),
                [self::class.':'.__LINE__, var_export($message, true)],
            );

            return;
        }

        /** Синхронно снимаем блокировку с заказа */

        $OrderUnlockMessage = new OrderUnlockMessage(
            id: $Order->getId(),
            context: self::class.':'.__LINE__
        );

        $this->messageDispatch->dispatch(
            message: $OrderUnlockMessage,
        );

        if(true === class_exists(BaksDevCentrifugoBundle::class))
        {
            /**
             * Отправляем сокет для скрытия заказа
             */
            $socket = $this->centrifugoPublish
                ->addData([
                    'order' => (string) $Order->getId(),
                    'profile' => false,
                    'context' => self::class.':'.__LINE__,
                ])
                ->send('orders');

            if($socket && $socket->isError())
            {
                $this->logger->critical(
                    message: 'orders-order: Ошибка при отправке информации о блокировке в Centrifugo',
                    context: [
                        $socket->getMessage(),
                        self::class.':'.__LINE__,
                    ],
                );
            }
        }


    }
}