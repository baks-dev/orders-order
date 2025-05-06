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

namespace BaksDev\Orders\Order\Repository\OrderEvent;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Repository\OrderInvariable\OrderInvariableInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;

final readonly class OrderEventRepository implements OrderEventInterface
{
    public function __construct(
        private ORMQueryBuilder $ORMQueryBuilder,
        private OrderInvariableInterface $OrderInvariableRepository,
    ) {}

    /**
     * Метод возвращает событие по идентификатору
     */
    public function find(OrderEventUid|string $event): OrderEvent|false
    {
        if(is_string($event))
        {
            $event = new OrderEventUid($event);
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('event')
            ->from(OrderEvent::class, 'event')
            ->where('event.id = :event')
            ->setParameter(
                key: 'event',
                value: $event,
                type: OrderEventUid::TYPE
            );


        /** @var OrderEvent $OrderEvent */
        $OrderEvent = $orm->getOneOrNullResult();

        /** Получаем активное состояние OrderInvariable если не определено */

        if(($OrderEvent instanceof OrderEvent) && false === $OrderEvent->isInvariable())
        {
            $OrderInvariable = $this->OrderInvariableRepository
                ->forOrder($OrderEvent->getMain())
                ->find();

            if(false === ($OrderInvariable instanceof OrderInvariable))
            {
                return false;
            }

            $OrderEvent->setInvariable($OrderInvariable);

        }

        return $OrderEvent ?: false;
    }
}
