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

namespace BaksDev\Orders\Order\Repository\OrderProfile;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;

use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\DBAL\Exception;
use InvalidArgumentException;

final class UpdateOrderProfileRepository implements UpdateOrderProfileInterface
{
    private ?UserProfileUid $profile = null;

    private ?OrderEventUid $event = null;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}


    public function profile(UserProfile|UserProfileUid|string $profile): self
    {
        if($profile instanceof UserProfile)
        {
            $this->profile = $profile->getId();
        }

        if($profile instanceof UserProfileUid)
        {
            $this->profile = $profile;
        }

        if(is_string($profile))
        {
            $this->profile = new UserProfileUid($profile);
        }

        return $this;
    }


    public function event(OrderEvent|OrderEventUid|string $event): self
    {
        if($event instanceof OrderEvent)
        {
            $this->event = $event->getId();
        }

        if($event instanceof OrderEventUid)
        {
            $this->event = $event;
        }

        if(is_string($event))
        {
            $this->event = new OrderEventUid($event);
        }

        return $this;
    }


    /**
     * Метод обновляет профиль пользователя ответственного лица
     */
    public function update(): int|string
    {

        if(!$this->event)
        {
            throw new InvalidArgumentException('Order Event is not null');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->update(OrderEvent::class);

        if($this->profile)
        {
            $dbal
                ->set('profile', ':profile')
                ->setParameter('profile', $this->profile, UserProfileUid::TYPE);
        }
        else
        {
            $dbal
                ->set('profile', ':profile')
                ->setParameter('profile', null);
        }


        $dbal
            ->where('id = :event')
            ->setParameter('event', $this->event, OrderEventUid::TYPE);

        $dbal->andWhere('profile IS NULL');

        return $dbal->executeStatement();

    }

    /**
     * Метод сбрасывает идентификаторы ответственных профилей заказов старше 1 часа
     */
    public function reset(): int|string
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->update(OrderEvent::class);

        $dbal
            ->set('profile', ':profile')
            ->setParameter('profile', null);

        $status = new OrderStatus(OrderStatusNew::class);

        $dbal
            ->andWhere("profile IS NOT NULL")
            ->andWhere("created < NOW() - INTERVAL '1 hour'")
            ->andWhere("status = :status")
            ->setParameter('status', $status, OrderStatus::TYPE);


        return $dbal->executeStatement();
    }

}
