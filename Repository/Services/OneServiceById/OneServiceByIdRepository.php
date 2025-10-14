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
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use RuntimeException;

/** Возвращает информацию об услуге по ее идентификатору */
final class OneServiceByIdRepository implements OneServiceByIdInterface
{
    private UserProfileUid|false $profile = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /** Фильтр по профилю */
    public function byProfile(UserProfileUid $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    /** Возвращает информацию об услуге по ее идентификатору */
    public function find(ServiceUid $service): OneServiceByIdResult|false
    {
        if(false === class_exists(BaksDevServicesBundle::class))
        {
            throw new RuntimeException('Не установлен зависимый модуль services');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

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

        /**
         * Invariable, Profile
         */

        //        if(false === $this->profile instanceof UserProfileUid)
        //        {
        //            $dbal
        //                ->join(
        //                    'service',
        //                    ServiceInvariable::class,
        //                    'service_invariable',
        //                    '
        //                        service_invariable.main = service.id
        //                        AND
        //                        service_invariable.profile = :'.$dbal::PROJECT_PROFILE_KEY
        //                );
        //
        //            /** Биндим параметр PROJECT_PROFILE_KEY */
        //            $dbal->isProjectProfile();
        //        }

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

        /** Price */
        $dbal
            ->addSelect('service_price.price')
            ->addSelect('service_price.currency')
            ->join(
                'service',
                ServicePrice::class,
                'service_price',
                'service_price.event = service.event'
            );

        /** Info */
        $dbal
            ->addSelect('service_info.name')
            ->addSelect('service_info.preview')
            ->join(
                'service',
                ServiceInfo::class,
                'service_info',
                'service_info.event = service.event'
            );

        /** Period */
        $dbal
            ->addSelect('service_period.frm')
            ->addSelect('service_period.upto')
            ->join(
                'service',
                ServicePeriod::class,
                'service_period',
                'service_period.event = service.event'
            );

        return $dbal->fetchHydrate(OneServiceByIdResult::class);
    }
}