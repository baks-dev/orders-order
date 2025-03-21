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

namespace BaksDev\Orders\Order\Repository\OrderNumber\NumberByOrderEvent;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use InvalidArgumentException;

final class NumberByOrderEventRepository implements NumberByOrderEventInterface
{
    private OrderEventUid|false $event = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forOrderEvent(OrderEvent|OrderEventUid|string $event): self
    {
        if($event instanceof OrderEvent)
        {
            $event = $event->getId();
        }

        if(is_string($event))
        {
            $event = new OrderEventUid($event);
        }

        $this->event = $event;

        return $this;
    }

    /**
     * Метод возвращает номер заказа по его идентификатору события
     */
    public function find(): string|bool
    {
        if(!$this->event instanceof OrderEventUid)
        {
            throw new InvalidArgumentException('Invalid Argument $event');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(OrderEvent::class, 'event')
            ->where('event.id = :event')
            ->setParameter('event', $this->event, OrderEventUid::TYPE);

        $dbal
            ->select('inv.number')
            ->join(
                'event',
                OrderInvariable::class,
                'inv',
                'inv.main = event.orders'
            );

        return $dbal
            ->enableCache('orders-order')
            ->fetchOne();
    }
}