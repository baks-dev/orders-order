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

namespace BaksDev\Orders\Order\Repository\OrdersDetailByProfile;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Delivery\Entity\Event\DeliveryEvent;
use BaksDev\Delivery\Entity\Price\DeliveryPrice;
use BaksDev\Delivery\Entity\Trans\DeliveryTrans;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\Delivery\Price\OrderDeliveryPrice;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Entity\User\Payment\OrderPayment;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Payment\Entity\Payment;
use BaksDev\Payment\Entity\Trans\PaymentTrans;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Stock\Orders\ProductStockOrder;
use BaksDev\Users\Address\Entity\GeocodeAddress;
use BaksDev\Users\Profile\TypeProfile\Entity\Section\Fields\Trans\TypeProfileSectionFieldTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\Section\Fields\TypeProfileSectionField;
use BaksDev\Users\Profile\TypeProfile\Entity\Trans\TypeProfileTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\TypeProfile;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Avatar\UserProfileAvatar;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Value\UserProfileValue;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Generator;
use InvalidArgumentException;

/** @see OrdersDetailByProfileRepositoryTest */
final class OrdersDetailByProfileRepository implements OrdersDetailByProfileInterface
{
    private UserProfileUid|UserUid|false $profile = false;

    private OrderStatus|false $status = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}

    /**
     * Заказы переданного профиля
     */
    public function forProfile(UserProfile|UserProfileUid|string $profile): self
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }

    /**
     * Заказы с переданным статусом
     */
    public function forStatus(OrderStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Метод возвращает пагинатор с информацией о заказев виде массивов
     * @deprecated
     */
    public function findAllWithPaginator(): PaginatorInterface
    {
        $result = $this->builder();
        return $this->paginator->fetchAllAssociative($result);
    }

    /**
     * Метод возвращает массивы с информацией о заказе
     * @return false|Generator<array>
     * @deprecated
     */
    public function findAll(): false|Generator
    {
        return $this->builder()->fetchAllGenerator();
    }

    /**
     * Метод возвращает резалты с информацией о заказе
     * @return false|Generator<OrdersDetailByProfileResult>
     */
    public function findAllResults(): false|Generator
    {
        return $this->builder()->fetchAllHydrate(OrdersDetailByProfileResult::class);
    }

    /** Метод возвращает пагинатор с информацией о заказе в виде резалтов */
    public function findAllWithResultPaginator(): PaginatorInterface
    {
        $result = $this->builder();
        return $this->paginator->fetchAllHydrate($result, OrdersDetailByProfileResult::class);
    }

    /** Билдер запроса */
    private function builder(): DBALQueryBuilder
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Не передан обязательный параметр запроса $profile');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('orders.id AS order_id')
            ->addSelect('orders.event AS order_event')
            ->addSelect('orders.number AS order_number')
            ->from(Order::class, 'orders');

        /** Актуальное состояние заказа */

        $dbal
            ->addSelect('event.status AS order_status')
            ->addSelect('event.created AS order_data')
            ->addSelect('event.comment AS order_comment');

        /** Фильтрация по статусу */
        if($this->status instanceof OrderStatus)
        {
            $dbal
                ->join(
                    'orders',
                    OrderEvent::class,
                    'event',
                    'event.id = orders.event AND event.status = :status'
                )
                ->setParameter(
                    key: 'status',
                    value: $this->status,
                    type: OrderStatus::TYPE
                );
        }
        else
        {
            $dbal
                ->join(
                    'orders',
                    OrderEvent::class,
                    'event',
                    'event.id = orders.event AND event.status != :completed AND event.status = :canceled'
                )
                ->setParameter('completed', OrderStatusCompleted::STATUS, OrderStatus::TYPE)
                ->setParameter('canceled', OrderStatusCanceled::STATUS, OrderStatus::TYPE);
        }


        $dbal
            ->leftJoin(
                'orders',
                OrderUser::class,
                'order_user',
                'order_user.event = orders.event'
            );


        /** Соответствие заказа переданному профилю пользователя */
        $dbal
            ->join(
                'order_user',
                UserProfileEvent::class,
                'user_profile_event',
                'user_profile_event.id = order_user.profile AND user_profile_event.profile = :profile'
            )
            ->setParameter(
                key: 'profile',
                value: $this->profile,
                type: UserProfileUid::TYPE
            );

        /** Оплата */
        $dbal
            ->leftJoin(
                'order_user',
                OrderPayment::class,
                'order_product_payment',
                'order_product_payment.usr = order_user.id'
            );

        $dbal
            ->addSelect('payment.id AS payment_id')
            ->leftJoin(
                'order_product_payment',
                Payment::class,
                'payment',
                'payment.id = order_product_payment.payment'
            );

        $dbal
            ->addSelect('payment_trans.name AS payment_name')
            ->leftJoin(
                'order_product_payment',
                PaymentTrans::class,
                'payment_trans',
                'payment_trans.event = payment.event AND payment_trans.local = :local'
            );

        /** Доставка */
        $dbal->leftJoin(
            'order_user',
            OrderDelivery::class,
            'order_delivery',
            'order_delivery.usr = order_user.id'
        );

        $dbal
            ->addSelect('order_delivery_price.price AS order_delivery_price')
            ->addSelect('order_delivery_price.currency AS order_delivery_currency')
            ->leftJoin(
                'order_delivery',
                OrderDeliveryPrice::class,
                'order_delivery_price',
                'order_delivery_price.delivery = order_delivery.id'
            );

        $dbal->leftJoin(
            'order_delivery',
            DeliveryEvent::class,
            'delivery_event',
            'delivery_event.id = order_delivery.event'
        );

        $dbal
            ->addSelect('delivery_trans.name AS delivery_name')
            ->leftJoin(
                'delivery_event',
                DeliveryTrans::class,
                'delivery_trans',
                'delivery_trans.event = order_delivery.event AND delivery_trans.local = :local'
            );

        $dbal
            ->addSelect('delivery_price.price AS delivery_price');

        $dbal->leftJoin(
            'delivery_event',
            DeliveryPrice::class,
            'delivery_price',
            'delivery_price.event = delivery_event.id'
        );

        /** Адрес доставки */
        $dbal->addSelect('delivery_geocode.longitude AS delivery_geocode_longitude');
        $dbal->addSelect('delivery_geocode.latitude AS delivery_geocode_latitude');
        $dbal->addSelect('delivery_geocode.address AS delivery_geocode_address');
        $dbal->leftJoin(
            'order_delivery',
            GeocodeAddress::class,
            'delivery_geocode',
            'delivery_geocode.latitude = order_delivery.latitude AND delivery_geocode.longitude = order_delivery.longitude'
        );

        /** Информация о профиле пользователя */
        $dbal
            ->addSelect('user_profile_info.discount AS order_profile_discount')
            ->leftJoin(
                'user_profile_event',
                UserProfileInfo::class,
                'user_profile_info',
                'user_profile_info.profile = user_profile_event.id'
            );

        $dbal->leftJoin(
            'user_profile_event',
            UserProfileValue::class,
            'user_profile_value',
            'user_profile_value.event = user_profile_event.id'
        );

        $dbal->leftJoin(
            'user_profile_event',
            TypeProfile::class,
            'type_profile',
            'type_profile.id = user_profile_event.type'
        );

        $dbal->addSelect('type_profile_trans.name AS order_profile')
            ->leftJoin(
                'type_profile',
                TypeProfileTrans::class,
                'type_profile_trans',
                'type_profile_trans.event = type_profile.event AND type_profile_trans.local = :local'
            );

        $dbal->leftJoin(
            'user_profile_value',
            TypeProfileSectionField::class,
            'type_profile_field',
            'type_profile_field.id = user_profile_value.field AND type_profile_field.card = true'
        );

        $dbal->leftJoin(
            'type_profile_field',
            TypeProfileSectionFieldTrans::class,
            'type_profile_field_trans',
            'type_profile_field_trans.field = type_profile_field.id AND type_profile_field_trans.local = :local'
        );

        /** Автарка профиля клиента */
        $dbal->addSelect("CONCAT ( '/upload/".$dbal->table(UserProfileAvatar::class)."' , '/', profile_avatar.name) AS profile_avatar_name");

        $dbal
            ->addSelect('profile_avatar.ext AS profile_avatar_ext')
            ->addSelect('profile_avatar.cdn AS profile_avatar_cdn')
            ->leftJoin(
                'user_profile_event',
                UserProfileAvatar::class,
                'profile_avatar',
                'profile_avatar.event = user_profile_event.id'
            );

        if(class_exists(BaksDevProductsStocksBundle::class))
        {
            /** Получаем информацию о складской заявке */
            $dbal->leftJoin(
                'orders',
                ProductStockOrder::class,
                'stock_order',
                'stock_order.ord = orders.id'
            );

            $dbal->leftJoin(
                'stock_order',
                ProductStockEvent::class,
                'stock_event',
                'stock_event.id = orders.id'
            );

        }

        $dbal->allGroupByExclude();

        $dbal->addOrderBy('event.created', 'DESC');


        return $dbal;
    }
}
