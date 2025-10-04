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

namespace BaksDev\Orders\Order\Repository\Services\ExistActiveServicePeriod;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Services\OrderService;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\OrderService\Period\ServicePeriodUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;

/** Проверяет наличие записи OrderService по дате и периоду */
final class ExistActiveOrderServiceRepository implements ExistActiveOrderServiceInterface
{
    private DateTimeImmutable|false $date = false;

    private ServicePeriodUid|false $period = false;

    private OrderEventUid|false $event = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function byDate(DateTimeImmutable $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function byPeriod(ServicePeriodUid $period): self
    {
        $this->period = $period;
        return $this;
    }

    public function byEvent(OrderEvent|OrderEventUid $event): self
    {
        if($event instanceof OrderEvent)
        {
            $event = $event->getId();
        }

        $this->event = $event;
        return $this;
    }

    /**
     * Проверяет наличие записи OrderService по дате и периоду
     */
    public function exist(): bool
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        if(false === $this->date instanceof DateTimeImmutable)
        {
            throw new InvalidArgumentException('Не передан обязательный параметр запроса $this->date');
        }

        if(false === $this->period instanceof ServicePeriodUid)
        {
            throw new InvalidArgumentException('Не передан обязательный параметр запроса $this->period');
        }

        $dbal
            ->select('orders_service.id')
            ->from(OrderService::class, 'orders_service');


        $dbal
            ->where('orders_service.date = :date')
            ->setParameter(
                key: 'date',
                value: $this->date,
                type: Types::DATE_IMMUTABLE,
            );

        $dbal
            ->andWhere('orders_service.period = :period')
            ->setParameter(
                key: 'period',
                value: $this->period,
                type: ServicePeriodUid::TYPE,
            );

        /** Услуги только на активные заказы */
        $dbal
            ->join(
                'orders_service',
                Order::class,
                'orders',
                'orders.event = orders_service.event',
            );

        if($this->event instanceof OrderEventUid)
        {
            $dbal
                ->andWhere('orders_service.event != :event')
                ->setParameter(
                    key: 'event',
                    value: $this->event,
                    type: OrderEventUid::TYPE,
                );
        }

        $dbal
            ->join(
                'orders',
                OrderEvent::class,
                'orders_event',

                'orders_event.id = orders.event AND orders_event.status != :status',
            )
            ->setParameter(
                key: 'status',
                value: OrderStatusCanceled::class,
                type: OrderStatus::TYPE,
            );


        return $dbal->fetchExist();
    }
}