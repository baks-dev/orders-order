<?php
/*
 * Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Repository\GetOldestUnpaidOrder;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusUnpaid;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class GetOldestUnpaidOrderRepository implements GetOldestUnpaidOrderInterface
{
    private UserProfileUid $seller;

    private UserProfileUid $profile;

    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    public function forSeller(UserProfileUid $seller): self
    {
        $this->seller = $seller;
        return $this;
    }

    public function forProfile(UserProfileUid $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    public function get(): ?OrderEvent
    {
        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('event')
            ->from(OrderEvent::class, 'event')
            ->where('event.status = :status')
            ->setParameter('status', OrderStatusUnpaid::STATUS, OrderStatus::TYPE);

        $orm
            ->join(
                OrderInvariable::class,
                'invariable',
                'WITH',
                'invariable.event = event.id AND invariable.profile = :seller'
            )
            ->setParameter('seller', $this->seller, UserProfileUid::TYPE);

        $orm->join(
            OrderUser::class,
            'user',
            'WITH',
            'user.event = event.id'
        );

        $orm
            ->join(
                UserProfileEvent::class,
                'profile_event',
                'WITH',
                'profile_event.profile = :profile'
            )
            ->setParameter('profile', $this->profile, UserProfileUid::TYPE);

        $orm->join(Order::class, 'main', 'WITH', 'main.id = event.orders');

        $orm->join(OrderModify::class, 'modify', 'WITH', 'modify.event = event.id');

        $orm->orderBy('modify.modDate');
        $orm->setMaxResults(1);

        return $orm->getOneOrNullResult();
    }
}