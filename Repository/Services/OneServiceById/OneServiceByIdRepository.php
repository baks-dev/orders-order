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
 *
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Repository\Services\OneServiceById;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Type\OrderService\Service\ServiceUid;
use BaksDev\Services\BaksDevServicesBundle;
use BaksDev\Services\Entity\Event\Info\ServiceInfo;
use BaksDev\Services\Entity\Event\Invariable\ServiceInvariable;
use BaksDev\Services\Entity\Event\Period\ServicePeriod;
use BaksDev\Services\Entity\Event\Price\ServicePrice;
use BaksDev\Services\Entity\Event\ServiceEvent;
use BaksDev\Services\Entity\Service;
use Exception;
use InvalidArgumentException;

/** Возвращает информацию об услуге по ее идентификатору */
final readonly class OneServiceByIdRepository implements OneServiceByIdInterface
{
    public function __construct(
        private DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /** Возвращает информацию об услуге по ее идентификатору */
    public function findOne(ServiceUid $service): OneServiceByIdResult|false
    {
        if(false === class_exists(BaksDevServicesBundle::class))
        {
            throw new Exception('Не установлен зависимый модуль services');
        }

        $builder = $this->builder($service);

        return $builder->fetchHydrate(OneServiceByIdResult::class);
    }

    private function builder(Service|ServiceUid $service): DBALQueryBuilder
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        if(false === $dbal->isProjectProfile())
        {
            throw new InvalidArgumentException('Не установлен PROJECT_PROFILE');
        }

        $dbal
            ->addSelect('service.id')
            ->addSelect('service.event')
            ->from(Service::class, 'service');

        /** Активное событие */
        $dbal
            ->join(
                'service',
                ServiceEvent::class,
                'service_event',
                '
                    service_event.main = service.id  
                    AND
                    service_event.main = :serv'
            )
            ->setParameter(
                key: 'serv',
                value: $service,
                type: ServiceUid::TYPE,
            );

        /** Invariable, Profile */
        $dbal
            ->join(
                'service',
                ServiceInvariable::class,
                'service_invariable',
                '
                        service_invariable.main = service.id
                        AND
                        service_invariable.profile = :'.$dbal::PROJECT_PROFILE_KEY
            );

        /** Price */
        $dbal
            ->addSelect('service_price.price')
            ->addSelect('service_price.currency')
            ->join(
                'service_event',
                ServicePrice::class,
                'service_price',
                'service_price.event = service_event.id'
            );

        /** Info */
        $dbal
            ->addSelect('service_info.name')
            ->addSelect('service_info.preview')
            ->join(
                'service_event',
                ServiceInfo::class,
                'service_info',
                'service_info.event = service_event.id'
            );

        /** Period */
        $dbal
            ->addSelect('service_period.frm')
            ->addSelect('service_period.upto')
            ->join(
                'service_event',
                ServicePeriod::class,
                'service_period',
                'service_period.event = service_event.id'
            );

        return $dbal;
    }
}