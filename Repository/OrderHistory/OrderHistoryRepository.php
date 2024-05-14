<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Repository\OrderHistory;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Entity\Avatar\UserProfileAvatar;
use BaksDev\Users\Profile\UserProfile\Entity\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;

final class OrderHistoryRepository implements OrderHistoryInterface
{


    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(DBALQueryBuilder $DBALQueryBuilder)
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }


    public function fetchHistoryAllAssociative(OrderUid|string $order): array
    {
        if(is_string($order))
        {
            $order = new OrderUid($order);
        }

        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->addSelect('event.status')
            ->from(OrderEvent::class, 'event')
            ->where('event.orders = :order')
            ->setParameter('order', $order, OrderUid::TYPE);

        $qb
            ->addSelect('modify.mod_date')
            ->addSelect('modify.action')
            ->leftJoin(
                'event',
                OrderModify::class,
                'modify',
                'modify.event = event.id'
            );


        $qb
            ->addSelect('order_user.profile AS order_profile_id')
            ->leftJoin(
                'event',
                OrderUser::class,
                'order_user',
                'order_user.event = event.id'
            );


        $qb->leftJoin(
            'modify',
            UserProfileInfo::class,
            'profile_info',
            'profile_info.usr = modify.usr AND profile_info.active = true'
        );


        $qb
            ->addSelect('profile.id AS user_profile_id')
            ->leftJoin(
                'profile_info',
                UserProfile::class,
                'profile',
                'profile.id = profile_info.profile'
            );

        $qb
            ->addSelect('profile_personal.username AS profile_username')
            ->leftJoin(
                'profile',
                UserProfilePersonal::class,
                'profile_personal',
                'profile_personal.event = profile.event'
            );


        $qb
            ->addSelect('profile_avatar.name AS profile_avatar_name')
            ->addSelect('profile_avatar.ext AS profile_avatar_ext')
            ->addSelect('profile_avatar.cdn AS profile_avatar_cdn')
            ->leftJoin(
                'profile',
                UserProfileAvatar::class,
                'profile_avatar',
                'profile_avatar.event = profile.event'
            );

        $qb->orderBy('modify.mod_date');

        return $qb->fetchAllAssociative();
    }

}