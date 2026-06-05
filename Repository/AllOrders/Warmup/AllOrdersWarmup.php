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
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Repository\AllOrders\Warmup;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusDecommission;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusReturn;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Прогреваем кеш репозитория
 *
 * @see ModelsByCategoryInterface
 */
#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: -999)]
final readonly class AllOrdersWarmup
{
    public function __construct(
        private MessageDispatchInterface $messageDispatch,
        private CurrentOrderEventInterface $currentOrderEventRepository,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        $OrderEvent = $this->currentOrderEventRepository
            ->forOrder($message->getId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            return;
        }

        /** @var OrderStatus $status */
        foreach(OrderStatus::cases() as $status)
        {
            if(
                $status->equals(OrderStatusCanceled::class)
                || $status->equals(OrderStatusReturn::class)
                || $status->equals(OrderStatusDecommission::class)
            )
            {
                continue;
            }

            $this->messageDispatch->dispatch(
                message: new AllOrdersWarmupMessage(
                    profile: $OrderEvent->getOrderProfile(),
                    status: $status,
                ),
                transport: 'async',
            );
        }
    }
}