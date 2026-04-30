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

namespace BaksDev\Orders\Order\UseCase\Admin\Status;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Entity\Lock\OrderLock;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

final class OrderStatusHandler extends AbstractHandler
{
    public function __construct(
        #[Target('ordersOrderLogger')] private readonly LoggerInterface $logger,
        private readonly ExistOrderEventByStatusInterface $existOrderEventByStatus,

        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload,
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);
    }


    /** @see Order */
    public function handle(OrderEventInterface $command, bool $deduplicator = true): string|Order
    {
        $this
            ->setCommand($command)
            ->preEventPersistOrUpdate(Order::class, OrderEvent::class);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        /**
         * Проверяем, если статус заказа может присваиваться только единожды
         */
        if($this->main instanceof Order && $deduplicator)
        {
            $exists = $this->existOrderEventByStatus
                ->forOrder($this->main->getId())
                ->forStatus($command->getStatus())
                ->isExists();

            if($exists)
            {
                return 'Невозможно применить повторно статус заказа';
            }
        }

        $this->flush();

        if($this->event instanceof OrderEvent)
        {
            $this->logger->info(
                message: sprintf('%s: заказ => %s обновили статус на %s',
                    $this->event->getPostingNumber() ?? $this->event->getOrderNumber(),
                    ($this->event->getLock() instanceof OrderLock) ?
                        ($this->event->getLock()->isLock() ? 'ЗАБЛОКИРОВАЛИ и' : 'НЕ БЛОКИРУЯ') : 'без блокировок',
                    $this->event->getStatus()->getOrderStatusValue(),
                ),
                context: [
                    self::class.':'.__LINE__,
                    (string) $this->main, (string) $this->event
                ],
            );
        }

        /** Отправляем сообщение в шину */
        $this->messageDispatch
            ->addClearCacheOther('orders-order-'.$this->getLastEvent()?->getStatus())
            ->addClearCacheOther('orders-order-'.$command->getStatus())
            ->addClearCacheOther('products-product')
            ->addClearCacheOther('products-stocks')
            ->dispatch(
                message: new OrderMessage(
                    $this->main->getId(),
                    $this->main->getEvent(),
                    $command->getEvent(),
                    $this->getLastEvent()?->getOrderProfile(),
                ),
                transport: 'orders-order',
            );

        return $this->main;
    }
}
