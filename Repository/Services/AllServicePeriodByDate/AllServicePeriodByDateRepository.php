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

namespace BaksDev\Orders\Order\Repository\Services\AllServicePeriodByDate;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Services\OrderService;
use BaksDev\Orders\Order\Type\OrderService\Service\ServiceUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use BaksDev\Services\BaksDevServicesBundle;
use BaksDev\Services\Entity\Event\Invariable\ServiceInvariable;
use BaksDev\Services\Entity\Event\Period\ServicePeriod;
use BaksDev\Services\Entity\Event\ServiceEvent;
use BaksDev\Services\Entity\Service;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Exception;
use Generator;
use InvalidArgumentException;

/** Возвращает массив периодов на переданную дату с информацией об их использовании */
final class AllServicePeriodByDateRepository implements AllServicePeriodByDateInterface
{
    private DateTimeImmutable|false $date = false;

    private UserProfileUid|false $profile = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function byDate(DateTimeImmutable $date): self
    {
        $this->date = $date;
        return $this;
    }

    /** Фильтр по профилю */
    public function byProfile(UserProfileUid $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    /**
     * Возвращает массив периодов на переданную дату с информацией об их использовании
     *
     * @return Generator{int, AllServicePeriodByDateResult}|false
     */
    public function findAll(ServiceUid $service): Generator|false
    {
        if(false === class_exists(BaksDevServicesBundle::class))
        {
            throw new Exception('Не установлен зависимый модуль services');
        }

        $builder = $this->builder($service);

        $result = $builder->fetchAllAssociative();

        if(true === empty($result))
        {
            return false;
        }

        $result = $this->unique($result);

        foreach($result as $serv)
        {
            yield new AllServicePeriodByDateResult(...$serv);
        }
    }

    /** Убирает дублирующийся неактивный период */
    private function unique(array $periods): array
    {
        $activePeriods = array_filter($periods, static function($element) {
            return $element['active_event'] === true;
        });

        $reservePeriods = array_filter($periods, static function($element) {
            return $element['active_event'] !== true && $element['order_service_active'];
        });

        foreach($activePeriods as $key => $period)
        {
            foreach($reservePeriods as $del => $reserve)
            {
                $periodFrm = new DateTimeImmutable($period['frm'])->format('H-s-i');
                $reserveFrm = new DateTimeImmutable($reserve['frm'])->format('H-s-i');

                $periodUpto = new DateTimeImmutable($period['upto'])->format('H-s-i');
                $reserveUpto = new DateTimeImmutable($reserve['upto'])->format('H-s-i');

                if($periodFrm === $reserveFrm && $periodUpto === $reserveUpto)
                {
                    $activePeriods[$key]['order_service_active'] = true;

                    /** Удаляем ключ на случай повторяющихся периодов */
                    unset($reservePeriods[$del]);

                    break;
                }

            }
        }

        return $activePeriods;
    }

    /** Возвращает массив периодов на переданную дату с информацией об их использовании */
    private function builder(ServiceUid $service): DBALQueryBuilder
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        if(false === $this->date instanceof DateTimeImmutable)
        {
            throw new InvalidArgumentException('Не передан обязательный параметр запроса $this->date');
        }

        $dbal->distinct();

        $dbal->from(Service::class, 'service');

        /** Активное событие */
        $dbal
            ->addSelect('(service_event.id::text = service.event::text) AS active_event')
            ->join(
                'service',
                ServiceEvent::class,
                'service_event',
                '
                    service_event.main = :serv
                    '
            )
            ->setParameter(
                key: 'serv',
                value: $service,
                type: ServiceUid::TYPE,
            );

        /**
         * Invariable, Profile
         */

        if(false === $this->profile instanceof UserProfileUid)
        {
            $dbal
                ->join(
                    'service',
                    ServiceInvariable::class,
                    'service_invariable',
                    '
                                service_invariable.main = service.id
                                AND
                                service_invariable.profile = :'.$dbal::PROJECT_PROFILE_KEY,
                );

            /** Биндим параметр PROJECT_PROFILE_KEY */
            $dbal->isProjectProfile();
        }

        if(true === $this->profile instanceof UserProfileUid)
        {
            $dbal
                ->join(
                    'service',
                    ServiceInvariable::class,
                    'service_invariable',
                    '
                        service_invariable.main = service.id
                        AND
                        service_invariable.profile = :profile'
                );

            $dbal->setParameter(
                key: 'profile',
                value: $this->profile,
                type: UserProfileUid::TYPE,
            );
        }

        /** Period */
        $dbal
            ->addSelect('service_period.id AS period_id')
            ->addSelect('service_period.frm')
            ->addSelect('service_period.upto')
            ->join(
                'service_event',
                ServicePeriod::class,
                'service_period',
                'service_period.event = service_event.id'
            );

        $dbal
            ->addSelect('orders_service.date as orders_service_date ')
            ->leftJoin(
                'service_period',
                OrderService::class,
                'orders_service',
                '
                    orders_service.period = service_period.id 
                    AND orders_service.date = :date'

            );

        $dbal->setParameter('date', $this->date, Types::DATE_IMMUTABLE);

        /** Все услуги (в т.ч. на неактивные заказы) */
        $dbal
            ->leftJoin(
                'orders_service',
                Order::class,
                'orders',
                'orders.event = orders_service.event'
            );

        $dbal
            ->leftJoin(
                'orders',
                OrderEvent::class,
                'orders_event',

                'orders_event.id = orders.event AND orders_event.status != :status'
            );

        $dbal->setParameter(
            key: 'status',
            value: OrderStatusCanceled::STATUS,
            type: OrderStatus::TYPE
        );

        $dbal
            ->addSelect('
                CASE
                    WHEN
                        orders_event.id = orders.event AND orders.event = orders_service.event 
                    THEN true
                    ELSE false
                END AS order_service_active
            ');

        $dbal->orderBy('service_period.frm');

        return $dbal;
    }
}