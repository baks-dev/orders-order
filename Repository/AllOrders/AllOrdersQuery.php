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

namespace BaksDev\Orders\Order\Repository\AllOrders;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Core\Services\Switcher\SwitcherInterface;
use BaksDev\Core\Type\Locale\Locale;

use BaksDev\Delivery\Entity\Event\DeliveryEvent;
use BaksDev\Delivery\Entity\Price\DeliveryPrice;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;

use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Forms\OrderFilter\OrderFilterDTO;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusInterface;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Move\ProductStockMove;
use BaksDev\Products\Stocks\Entity\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Entity\ProductStock;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus;

use BaksDev\Users\Profile\TypeProfile\Entity\Section\Fields\Trans\TypeProfileSectionFieldTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\Section\Fields\TypeProfileSectionField;
use BaksDev\Users\Profile\TypeProfile\Entity\Trans\TypeProfileTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\TypeProfile;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Entity\Value\UserProfileValue;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AllOrdersQuery implements AllOrdersInterface
{
    private PaginatorInterface $paginator;

    private DBALQueryBuilder $DBALQueryBuilder;

    private ?SearchDTO $search = null;

    private ?OrderStatus $status = null;

    private ?OrderFilterDTO $filter = null;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator,
    )
    {
        $this->paginator = $paginator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function status(OrderStatus|OrderStatusInterface|string $status): self
    {
        $this->status = new OrderStatus($status);
        return $this;
    }

    public function filter(OrderFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Метод возвращает список заказов
     */
    public function findAllPaginator(UserProfileUid|UserUid $usr): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('orders.id AS order_id')
            ->addSelect('orders.event AS order_event')
            ->addSelect('orders.number AS order_number')
            ->from(Order::TABLE, 'orders');


        $dbal
            ->addSelect('order_event.created AS order_created')
            ->addSelect('order_event.status AS order_status')
            ->addSelect('order_event.comment AS order_comment');

        if($this->status?->equals(OrderStatusNew::class))
        {
            $dbal
                ->join(
                    'orders',
                    OrderEvent::TABLE,
                    'order_event',
                    'order_event.id = orders.event AND (order_event.profile = :profile OR order_event.profile IS NULL)'

                );
        }
        else
        {
            $dbal
                ->join(
                    'orders',
                    OrderEvent::TABLE,
                    'order_event',
                    'order_event.id = orders.event AND order_event.profile IS NOT NULL'

                );

            //'order_event.id = orders.event '.($usr instanceof UserProfileUid ? ' AND (order_event.profile IS NULL OR order_event.profile = :profile)' : '').'

            $dbal
                ->andWhereExists(
                    OrderEvent::class,
                    'profile_exists',
                    'profile_exists.orders = order_event.orders AND profile_exists.profile = :profile'
                );
        }


        $dbal->setParameter('profile', $usr, UserProfileUid::TYPE);


        $dbal
            ->addSelect('orders_modify.mod_date AS modify')
            ->leftJoin(
                'orders',
                OrderModify::TABLE,
                'orders_modify',
                'orders_modify.event = orders.event'
            );

        if($this->status)
        {
            $dbal
                ->andWhere('order_event.status = :status')
                ->setParameter('status', $this->status, OrderStatus::TYPE);
        }


        if($this->filter?->getStatus())
        {
            $dbal
                ->andWhere('order_event.status = :status')
                ->setParameter('status', $this->filter->getStatus(), OrderStatus::TYPE);
        }


        // Продукция

        if(!$this->search?->getQuery() && $this->filter?->getDate())
        {
            $date = $this->filter->getDate() ?: new DateTimeImmutable();

            // Начало дня
            $startOfDay = $date->setTime(0, 0, 0);
            // Конец дня
            $endOfDay = $date->setTime(23, 59, 59);

            //($date ? ' AND part_modify.mod_date = :date' : '')
            $dbal->andWhere('orders_modify.mod_date BETWEEN :start AND :end');

            $dbal->setParameter('start', $startOfDay, 'datetime_immutable');
            $dbal->setParameter('end', $endOfDay, 'datetime_immutable');
        }

        $dbal
            ->leftJoin(
                'orders',
                OrderProduct::TABLE,
                'order_products',
                'order_products.event = orders.event'
            );


        $dbal
            ->addSelect('order_products_price.currency AS order_currency')
            ->leftJoin(
                'order_products',
                OrderPrice::TABLE,
                'order_products_price',
                'order_products_price.product = order_products.id'
            );


        $dbal
            ->leftJoin(
                'orders',
                OrderUser::TABLE,
                'order_user',
                'order_user.event = orders.event'
            );

        // Доставка


        $dbal
            ->addSelect('order_delivery.delivery_date AS delivery_date')
            ->leftJoin(
                'order_user',
                OrderDelivery::TABLE,
                'order_delivery',
                'order_delivery.usr = order_user.id'
            );

        $dbal
            ->leftJoin(
                'order_delivery',
                DeliveryEvent::TABLE,
                'delivery_event',
                'delivery_event.id = order_delivery.event'
            );

        $dbal
            ->addSelect('delivery_price.price AS delivery_price')
            ->leftJoin(
                'delivery_event',
                DeliveryPrice::TABLE,
                'delivery_price',
                'delivery_price.event = delivery_event.id'
            );


        // Профиль пользователя (Клиент)

        $dbal
            ->leftJoin(
                'order_user',
                UserProfileEvent::TABLE,
                'user_profile',
                'user_profile.id = order_user.profile'
            );

        $dbal
            ->addSelect('user_profile_info.discount AS order_profile_discount')
            ->leftJoin(
                'user_profile',
                UserProfileInfo::TABLE,
                'user_profile_info',
                'user_profile_info.profile = user_profile.profile ' //.($usr instanceof UserUid ? ' AND (user_profile_info.usr IS NULL OR user_profile_info.usr = :user)' : '')
            )//->setParameter('user', $usr, UserUid::TYPE)
        ;


        $dbal
            ->leftJoin(
                'user_profile',
                UserProfileValue::TABLE,
                'user_profile_value',
                'user_profile_value.event = user_profile.id'
            );


        $dbal
            ->leftJoin(
                'user_profile',
                TypeProfile::TABLE,
                'type_profile',
                'type_profile.id = user_profile.type'
            );


        /** Название типа профиля */
        $dbal
            ->addSelect('type_profile_trans.name AS order_profile')
            ->leftJoin(
                'type_profile',
                TypeProfileTrans::TABLE,
                'type_profile_trans',
                'type_profile_trans.event = type_profile.event AND type_profile_trans.local = :local'
            );

        $dbal
            ->leftJoin(
                'user_profile_value',
                TypeProfileSectionField::TABLE,
                'type_profile_field',
                'type_profile_field.id = user_profile_value.field AND type_profile_field.card = true'
            );

        $dbal
            ->leftJoin(
                'type_profile_field',
                TypeProfileSectionFieldTrans::TABLE,
                'type_profile_field_trans',
                'type_profile_field_trans.field = type_profile_field.id AND type_profile_field_trans.local = :local'
            );

        $dbal->addSelect(
            "JSON_AGG
			( DISTINCT
				
					JSONB_BUILD_OBJECT
					(
						/* свойства для сортирвоки JSON */
						'0', type_profile_field.sort,

						'profile_type', type_profile_field.type,
						'profile_name', type_profile_field_trans.name,
						'profile_value', user_profile_value.value
					)
				
			)
			AS order_user"
        );


        $dbal->addSelect('FALSE AS order_move');


        // если имеется таблица складского учета - проверяем, имеется ли заказ в перемещении

        if(!$this->status?->equals(OrderStatus\OrderStatusNew::class) && class_exists(ProductStock::class))
        {
            $dbalExist = $this->DBALQueryBuilder->builder();

            $dbalExist
                ->select('1')
                ->from(ProductStockMove::TABLE, 'move')
                ->where('move.ord = orders.id');

            $dbalExist->join(
                'move',
                ProductStockEvent::TABLE,
                'move_event',
                'move_event.id = move.event AND (move_event.status = :moving OR move_event.status = :extradition)'
            );


            $dbalExist->join(
                'move_event',
                ProductStock::TABLE,
                'move_stock',
                'move_stock.event = move_event.id'
            );

            $dbal->addSelect(sprintf('EXISTS(%s) AS order_move', $dbalExist->getSQL()));


            $dbal->setParameter('moving', new ProductStockStatus(new ProductStockStatus\ProductStockStatusMoving()), ProductStockStatus::TYPE);
            $dbal->setParameter('extradition', new ProductStockStatus(new ProductStockStatus\ProductStockStatusExtradition()), ProductStockStatus::TYPE);


            $dbal
                ->leftJoin(
                    'orders',
                    ProductStockOrder::class,
                    'stock_order',
                    'stock_order.ord = orders.id'
                );

            $dbal
                ->leftJoin(
                    'stock_order',
                    ProductStock::class,
                    'stock',
                    'stock.event = stock_order.event'
                );


            $dbal
                ->leftJoin(
                    'stock_order',
                    ProductStockEvent::class,
                    'stock_event',
                    'stock_event.id = stock_order.event'
                );


            $dbal
                ->leftJoin(
                    'stock_event',
                    UserProfile::class,
                    'stock_profile',
                    'stock_profile.id = stock_event.profile'
                );


            //$dbal->addSelect('FALSE AS stock_profile_username')
            //->addSelect('FALSE AS stock_profile_location');

            $dbal
                ->addSelect('stock_profile_personal.username AS stock_profile_username')
                ->addSelect('stock_profile_personal.location AS stock_profile_location')
                ->leftJoin(
                    'stock_event',
                    UserProfilePersonal::class,
                    'stock_profile_personal',
                    'stock_profile_personal.event = stock_event.profile'
                );


            // Профиль ответственного (Клиент)

            //            $dbal->leftJoin(
            //                'order_user',
            //                UserProfileEvent::TABLE,
            //                'user_profile',
            //                'user_profile.id = order_user.profile'
            //            );


        }
        else
        {
            $dbal->addSelect('FALSE AS order_move');
        }


        // если имеется таблица доставки транспортом - проверяем, имеется ли заказ с ошибкой погрузки транспорта
        if(class_exists(DeliveryTransport::class))
        {
            $dbalExistMoveError = $this->DBALQueryBuilder->builder();
            $dbalExistMoveError->select('1');

            $dbalExistMoveError->from(ProductStockMove::TABLE, 'move');
            $dbalExistMoveError->where('move.ord = orders.id');

            $dbalExistMoveError->join(
                'move',
                ProductStockEvent::TABLE,
                'move_event',
                'move_event.id = move.event AND move_event.status = :error'
            );

            $dbalExistMoveError->join(
                'move_event',
                ProductStock::TABLE,
                'move_stock',
                'move_stock.event = move_event.id'
            );

            $dbal->addSelect(sprintf('EXISTS(%s) AS move_error', $dbalExistMoveError->getSQL()));


            $dbalExistOrderError = $this->DBALQueryBuilder->builder();

            $dbalExistOrderError->select('1');

            $dbalExistOrderError->from(ProductStockOrder::TABLE, 'stock_order');
            $dbalExistOrderError->where('stock_order.ord = orders.id');

            $dbalExistOrderError->join(
                'stock_order',
                ProductStockEvent::TABLE,
                'stock_order_event',
                'stock_order_event.id = stock_order.event AND stock_order_event.status = :error'
            );

            $dbalExistOrderError->join(
                'stock_order_event',
                ProductStock::TABLE,
                'stock_order_stock',
                'stock_order_stock.event = stock_order_event.id'
            );

            $dbal->addSelect(sprintf('EXISTS(%s) AS order_error', $dbalExistOrderError->getSQL()));

            $dbal->setParameter('error', new ProductStockStatus(new ProductStockStatus\ProductStockStatusError()), ProductStockStatus::TYPE);
        }
        else
        {
            $dbal->addSelect('FALSE AS move_error');
            $dbal->addSelect('FALSE AS order_error');
        }


        $dbal->addSelect(
            "JSON_AGG
			( DISTINCT
		
					JSONB_BUILD_OBJECT
					(
						'product', order_products_price.product,
						'price', order_products_price.price,
						'total', order_products_price.total
					)
		
			)
			AS product_price"
        );


        if($this->search?->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('orders.id')
                ->addSearchEqualUid('orders.event')
                ->addSearchLike('orders.number')
                ->addSearchLike('user_profile_value.value')
                //                ->addSearchLike('product_offer.article')
                //                ->addSearchLike('product_offer_modification.article')
                //                ->addSearchLike('product_offer_variation.article')
            ;
        }


        if((string) $this->status === 'new')
        {
            $dbal->addOrderBy('orders_modify.mod_date', 'ASC');
        }
        else
        {
            $dbal->addOrderBy('orders_modify.mod_date', 'DESC');
        }


        $dbal->allGroupByExclude();


        //$dbal->setMaxResults(500);


        return $this->paginator->fetchAllAssociative($dbal);
    }
}
