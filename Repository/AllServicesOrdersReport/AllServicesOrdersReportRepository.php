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

namespace BaksDev\Orders\Order\Repository\AllServicesOrdersReport;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Services\OrderService;
use BaksDev\Orders\Order\Entity\Services\Price\OrderServicePrice;
use BaksDev\Services\Entity\Event\Info\ServiceInfo;
use BaksDev\Services\Entity\Event\Price\ServicePrice;
use BaksDev\Services\Entity\Service;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Generator;

final class AllServicesOrdersReportRepository implements AllServicesOrdersReportInterface
{
    private DateTimeImmutable $from;

    private DateTimeImmutable $to;

    private UserProfileUid|false $profile = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function from(DateTimeImmutable $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function to(DateTimeImmutable $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function forProfile(UserProfile|UserProfileUid $profile): self
    {
        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }

    /**
     * Возвращает данные для составления отчета по услугам в заказах за определенный период
     *
     * @return Generator<int, AllServicesOrdersReportResult>|false
     */
    public function findAll(): Generator|false
    {

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal->from(Order::class, "orders");

        $dbal
            ->addSelect('orders_event.danger')
            ->addSelect('orders_event.comment')
            ->join(
                'orders',
                OrderEvent::class,
                "orders_event",
                "
                    orders_event.id = orders.event AND
                    orders_event.status = 'completed'
                ",
            );


        /* Дата изменения */
        $dbal
            ->addSelect('orders_modify.mod_date AS mod_date')
            ->join(
                'orders',
                OrderModify::class,
                'orders_modify',
                'orders_modify.event = orders.event AND DATE(orders_modify.mod_date) BETWEEN :date_from AND :date_to',

            )
            ->setParameter(
                key: 'date_from',
                value: $this->from,
                type: Types::DATE_IMMUTABLE,
            )
            ->setParameter(
                key: 'date_to',
                value: $this->to,
                type: Types::DATE_IMMUTABLE,
            );


        /* Номер заказа */
        $dbal
            ->addSelect("orders_invariable.number AS number")
            ->join(
                "orders",
                OrderInvariable::class,
                "orders_invariable",
                "orders_invariable.main = orders.id"
                .($this->profile instanceof UserProfileUid ? ' AND orders_invariable.profile = :profile' : ''),
            );

        if($this->profile instanceof UserProfileUid)
        {
            $dbal->setParameter(
                key: 'profile',
                value: $this->profile,
                type: UserProfileUid::TYPE,
            );
        }


        /** Услуги */
        $dbal
            ->leftJoin(
                'order_service',
                Service::class,
                'service',
                'service.id = order_service.serv',
            );

        $dbal->join(
            'orders',
            OrderService::class,
            'order_service',
            'order_service.event = orders.event',
        );

        /* Цена услуги в заказе */
        $dbal
            ->addSelect('order_service_price.price as order_service_price')
            ->leftJoin(
                'orders',
                OrderServicePrice::class,
                'order_service_price',
                'order_service_price.serv = order_service.id',
            );


        /* Название услуги */
        $dbal
            ->addSelect('service_info.name as service_name')
            ->leftJoin(
                'service',
                ServiceInfo::class,
                'service_info',
                'service_info.event = service.event',
            );

        /* Цена услуги */
        $dbal
            ->addSelect('service_price.price as service_price')
            ->leftJoin('service',
                ServicePrice::class,
                'service_price',
                'service.event = service_price.event'
            );


        $dbal->allGroupByExclude();

        $dbal->orderBy("orders_modify.mod_date");

        $result = $dbal->fetchAllHydrate(AllServicesOrdersReportResult::class);

        return $result->valid() ? $result : false;
    }

}