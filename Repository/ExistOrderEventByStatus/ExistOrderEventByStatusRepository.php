<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;

final class ExistOrderEventByStatusRepository implements ExistOrderEventByStatusInterface
{
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
    )
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /**
     * Метод проверяет, имеется ли событие у заказа с указанным статусом
     */
    public function isExists(OrderUid|string $order, OrderStatus|OrderStatusInterface|string $status): bool
    {
        if(is_string($order))
        {
            $order = new OrderUid($order);
        }

        if(is_string($status) || $status instanceof OrderStatusInterface)
        {
            $status = new OrderStatus($status);
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(OrderEvent::class, 'event')

            ->where('event.orders = :ord')
            ->setParameter('ord', $order, OrderUid::TYPE)

            ->andWhere('event.status = :status')
            ->setParameter('status', $status, OrderStatus::TYPE)
        ;

        return $dbal->fetchExist();
    }


    /**
     * Метод проверяет, имеется ли другое событие заказа с указанным статусом
     */
    public function isOtherExists(
        OrderUid|string $order,
        OrderEventUid|string $event,
        OrderStatus|OrderStatusInterface|string $status
    ): bool
    {
        if(is_string($order))
        {
            $order = new OrderUid($order);
        }

        if(is_string($event))
        {
            $event = new OrderEventUid($event);
        }

        if(is_string($status) || $status instanceof OrderStatusInterface)
        {
            $status = new OrderStatus($status);
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);


        $dbal
            ->from(Order::class, 'main')
            ->where('main.id = :ord')
            ->setParameter('ord', $order, OrderUid::TYPE)
        ;

        $dbal
            ->join(
                'main',
                OrderEvent::class,
                'event',
                'event.orders = main.id AND event.id != :event AND event.status = :status'
            )
            ->setParameter('event', $event, OrderEventUid::TYPE)
            ->setParameter('status', $status, OrderStatus::TYPE)
        ;

        $dbal->andWhere('main.event = event.id');

        return $dbal->fetchExist();
    }

}