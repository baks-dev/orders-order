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

namespace BaksDev\Orders\Order\Repository\ExistOrderEventByStatus;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusInterface;
use InvalidArgumentException;

final class ExistOrderEventByStatusRepository implements ExistOrderEventByStatusInterface
{
    private OrderUid|false $order = false;

    private OrderEventUid|false $event = false;

    private OrderStatus|false $status = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forOrder(Order|OrderUid $order): self
    {
        if($order instanceof Order)
        {
            $order = $order->getId();
        }

        $this->order = $order;

        return $this;
    }

    public function forOrderEvent(OrderEvent|OrderEventUid $event): self
    {

        if($event instanceof OrderEvent)
        {
            $event = $event->getId();
        }

        $this->event = $event;

        return $this;
    }

    public function forStatus(OrderStatus|OrderStatusInterface|string $status): self
    {

        if(is_string($status) || $status instanceof OrderStatusInterface)
        {
            $status = new OrderStatus($status);
        }

        $this->status = $status;

        return $this;
    }


    /**
     * Метод проверяет, имеется ли событие у заказа с указанным статусом
     */
    public function isExists(): bool
    {
        if(false === ($this->order instanceof OrderUid))
        {
            throw new InvalidArgumentException('Invalid Argument OrderUid');
        }

        if(false === ($this->status instanceof OrderStatus))
        {
            throw new InvalidArgumentException('Invalid Argument OrderStatus');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(OrderEvent::class, 'event');

        $dbal
            ->where('event.orders = :ord')
            ->setParameter(
                key: 'ord',
                value: $this->order,
                type: OrderUid::TYPE,
            );

        $dbal
            ->andWhere('event.status = :status')
            ->setParameter(
                key: 'status',
                value: $status,
                type: OrderStatus::TYPE,
            );

        return $dbal->fetchExist();
    }


    /**
     * Метод проверяет, имеется ли другое событие заказа с указанным статусом
     */
    public function isOtherExists(): bool
    {
        if(false === ($this->order instanceof OrderUid))
        {
            throw new InvalidArgumentException('Invalid Argument OrderUid');
        }

        if(false === ($this->event instanceof OrderEventUid))
        {
            throw new InvalidArgumentException('Invalid Argument OrderEventUid');
        }

        if(false === ($this->status instanceof OrderStatus))
        {
            throw new InvalidArgumentException('Invalid Argument OrderStatus');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(Order::class, 'main')
            ->where('main.id = :ord')
            ->setParameter(
                key: 'ord',
                value: $this->order,
                type: OrderUid::TYPE,
            );

        $dbal
            ->join(
                'main',
                OrderEvent::class,
                'event',
                'event.orders = main.id AND event.id != :event AND event.status = :status',
            )
            ->setParameter(
                key: 'event',
                value: $this->event,
                type: OrderEventUid::TYPE,
            )
            ->setParameter(
                key: 'status',
                value: $this->status,
                type: OrderStatus::TYPE,
            );

        $dbal->andWhere('main.event = event.id');

        return $dbal->fetchExist();
    }
}
