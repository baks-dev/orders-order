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

namespace BaksDev\Orders\Order\Repository\OrderHistory;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Avatar\UserProfileAvatar;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use Generator;

final class OrderHistoryRepository implements OrderHistoryInterface
{
    private ?OrderUid $order = null;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}


    public function order(Order|OrderUid|string $order): self
    {
        if($order instanceof Order)
        {
            $order = $order->getId();
        }

        if(is_string($order))
        {
            $order = new OrderUid($order);
        }

        $this->order = $order;

        return $this;
    }

    private function builder(): DBALQueryBuilder
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('event.id as event_id')
            ->addSelect('event.status')
            ->from(OrderEvent::class, 'event');

        if($this->order)
        {
            $dbal
                ->where('event.orders = :order')
                ->setParameter('order', $this->order, OrderUid::TYPE);
        }


        $dbal
            ->addSelect('modify.mod_date')
            ->addSelect('modify.action')
            ->leftJoin(
                'event',
                OrderModify::class,
                'modify',
                'modify.event = event.id',
            );


        $dbal
            ->addSelect('order_user.profile AS order_profile_id')
            ->leftJoin(
                'event',
                OrderUser::class,
                'order_user',
                'order_user.event = event.id',
            );


        $dbal->leftJoin(
            'modify',
            UserProfileInfo::class,
            'profile_info',
            'profile_info.usr = modify.usr AND profile_info.active = true',
        );


        $dbal
            ->addSelect('profile.id AS user_profile_id')
            ->leftJoin(
                'profile_info',
                UserProfile::class,
                'profile',
                'profile.id = profile_info.profile',
            );

        $dbal
            ->addSelect('profile_personal.username AS profile_username')
            ->leftJoin(
                'profile',
                UserProfilePersonal::class,
                'profile_personal',
                'profile_personal.event = profile.event',
            );


        $dbal
            ->addSelect('profile_avatar.name AS profile_avatar_name')
            ->addSelect('profile_avatar.ext AS profile_avatar_ext')
            ->addSelect('profile_avatar.cdn AS profile_avatar_cdn')
            ->leftJoin(
                'profile',
                UserProfileAvatar::class,
                'profile_avatar',
                'profile_avatar.event = profile.event',
            );

        $dbal->orderBy('modify.mod_date');

        return $dbal;
    }


    /**
     * @deprecated
     * Получаем информацию о предыдущих событиях заказа (в виде массива))
     */
    public function findAllHistory(): array
    {
        $dbal = $this->builder();

        return $dbal->fetchAllAssociative();
    }


    /**
     * Получаем информацию о предыдущих событиях заказа (в виде резалта))
     * @return Generator<OrderHistoryResult>
     */
    public function findAllHistoryResult(): Generator
    {
        $dbal = $this->builder();

        return $dbal->fetchAllHydrate(OrderHistoryResult::class);
    }
}
