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
use BaksDev\Delivery\Entity as DeliveryEntity;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;

use BaksDev\Orders\Order\Entity as OrderEntity;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Move\ProductStockMove;
use BaksDev\Products\Stocks\Entity\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Entity\ProductStock;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus;
use BaksDev\Users\Profile\TypeProfile\Entity as TypeProfileEntity;
use BaksDev\Users\Profile\UserProfile\Entity as UserProfileEntity;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AllOrdersQuery implements AllOrdersInterface
{

    private PaginatorInterface $paginator;

    private TranslatorInterface $translator;
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
       DBALQueryBuilder $DBALQueryBuilder,
        TranslatorInterface $translator,
        SwitcherInterface $switcher,
        PaginatorInterface $paginator,
    ) {

        // $this->switcher = $switcher;
        $this->paginator = $paginator;
        $this->translator = $translator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /** Метод возвращает список заказов согласно статусу. Если передан профиль пользователя - то список заказов только принадлежащие данному профилю */
    public function fetchAllOrdersAssociative(
        OrderStatus $status,
        SearchDTO $search,
        ?UserProfileUid $profile
    ): PaginatorInterface
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        /** ЛОКАЛЬ */
        $locale = new Locale($this->translator->getLocale());
        $qb->setParameter('local', $locale, Locale::TYPE);

        $qb->select('orders.id AS order_id')->addGroupBy('orders.id');
        $qb->addSelect('orders.event AS order_event')->addGroupBy('orders.event');
        $qb->addSelect('orders.number AS order_number')->addGroupBy('orders.number');
        $qb->from(OrderEntity\Order::TABLE, 'orders');

        $qb->addSelect('order_event.created AS order_created')->addGroupBy('order_event.created');
        $qb->addSelect('order_event.status AS order_status')->addGroupBy('order_event.status');
        $qb->join(
            'orders',
            OrderEntity\Event\OrderEvent::TABLE,
            'order_event',
            'order_event.status = :status AND order_event.id = orders.event '.($profile ? 'AND (order_event.profile IS NULL OR order_event.profile = :profile)' : '')
        );

        if ($profile)
        {
            $qb->setParameter('profile', $profile, UserProfileUid::TYPE);
        }

        $qb->setParameter('status', $status, OrderStatus::TYPE);



        // Продукция

        $qb->addSelect('order_products_price.currency AS order_currency')
            ->addGroupBy('order_products_price.currency');

        $qb->leftJoin(
            'orders',
            OrderEntity\Products\OrderProduct::TABLE,
            'order_products',
            'order_products.event = orders.event'
        );


        $qb->addSelect(
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

        $qb->leftJoin(
            'order_products',
            OrderEntity\Products\Price\OrderPrice::TABLE,
            'order_products_price',
            'order_products_price.product = order_products.id'
        );

        $qb->leftJoin(
            'orders',
            OrderEntity\User\OrderUser::TABLE,
            'order_user',
            'order_user.event = orders.event'
        );

        // Доставка

        $qb->leftJoin(
            'order_user',
            OrderEntity\User\Delivery\OrderDelivery::TABLE,
            'order_delivery',
            'order_delivery.usr = order_user.id'
        );

        $qb->leftJoin(
            'order_delivery',
            DeliveryEntity\Event\DeliveryEvent::TABLE,
            'delivery_event',
            'delivery_event.id = order_delivery.event'
        );

        $qb->addSelect('delivery_price.price AS delivery_price')
            ->addGroupBy('delivery_price.price');
        $qb->leftJoin(
            'delivery_event',
            DeliveryEntity\Price\DeliveryPrice::TABLE,
            'delivery_price',
            'delivery_price.event = delivery_event.id'
        );

        // Профиль пользователя

        $qb->leftJoin(
            'order_user',
            UserProfileEntity\Event\UserProfileEvent::TABLE,
            'user_profile',
            'user_profile.id = order_user.profile'
        );

        $qb->addSelect('user_profile_info.discount AS order_profile_discount')->addGroupBy(
            'user_profile_info.discount'
        );

        $qb->leftJoin(
            'user_profile',
            UserProfileEntity\Info\UserProfileInfo::TABLE,
            'user_profile_info',
            'user_profile_info.profile = user_profile.profile'
        );

        $qb->leftJoin(
            'user_profile',
            UserProfileEntity\Value\UserProfileValue::TABLE,
            'user_profile_value',
            'user_profile_value.event = user_profile.id'
        );

        $qb->leftJoin(
            'user_profile',
            TypeProfileEntity\TypeProfile::TABLE,
            'type_profile',
            'type_profile.id = user_profile.type'
        );

        $qb->addSelect('type_profile_trans.name AS order_profile')->addGroupBy('type_profile_trans.name');
        $qb->leftJoin(
            'type_profile',
            TypeProfileEntity\Trans\TypeProfileTrans::TABLE,
            'type_profile_trans',
            'type_profile_trans.event = type_profile.event AND type_profile_trans.local = :local'
        );

        $qb->join(
            'user_profile_value',
            TypeProfileEntity\Section\Fields\TypeProfileSectionField::TABLE,
            'type_profile_field',
            'type_profile_field.id = user_profile_value.field AND type_profile_field.card = true'
        );

        $qb->leftJoin(
            'type_profile_field',
            TypeProfileEntity\Section\Fields\Trans\TypeProfileSectionFieldTrans::TABLE,
            'type_profile_field_trans',
            'type_profile_field_trans.field = type_profile_field.id AND type_profile_field_trans.local = :local'
        );

        $qb->addSelect(
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

        // если имеется таблица складского учета - проверяем, имеется ли заказ в перемещении
        if (defined(ProductStock::class.'::TABLE'))
        {
            $qbExist = $this->DBALQueryBuilder->builder();

            $qbExist->select('1');
            $qbExist->from(ProductStockMove::TABLE, 'move');
            $qbExist->where('move.ord = orders.id');

            $qbExist->join(
                'move',
                ProductStockEvent::TABLE,
                'move_event',
                'move_event.id = move.event AND (move_event.status = :moving OR move_event.status = :extradition)'
            );

            $qbExist->join(
                'move_event',
                ProductStock::TABLE,
                'move_stock',
                'move_stock.event = move_event.id'
            );

            $qb->addSelect(sprintf('EXISTS(%s) AS order_move', $qbExist->getSQL()) );


            $qb->setParameter('moving', new ProductStockStatus(new ProductStockStatus\ProductStockStatusMoving()), ProductStockStatus::TYPE);
            $qb->setParameter('extradition', new ProductStockStatus(new ProductStockStatus\ProductStockStatusExtradition()), ProductStockStatus::TYPE);



        }
        else
        {
            $qb->addSelect('FALSE AS order_move');

        }



        // если имеется таблица доставки транспортом - проверяем, имеется ли заказ с ошибкой погрузки транспорта
        if (defined(DeliveryTransport::class.'::TABLE'))
        {

            $qbExistMoveError = $this->DBALQueryBuilder->builder();

            $qbExistMoveError->select('1');


            $qbExistMoveError->from(ProductStockMove::TABLE, 'move');
            $qbExistMoveError->where('move.ord = orders.id');

            $qbExistMoveError->join(
                'move',
                ProductStockEvent::TABLE,
                'move_event',
                'move_event.id = move.event AND move_event.status = :error'
            );

            $qbExistMoveError->join(
                'move_event',
                ProductStock::TABLE,
                'move_stock',
                'move_stock.event = move_event.id'
            );

            $qb->addSelect(sprintf('EXISTS(%s) AS move_error', $qbExistMoveError->getSQL()) );


            $qbExistOrderError = $this->DBALQueryBuilder->builder();

            $qbExistOrderError->select('1');

            $qbExistOrderError->from(ProductStockOrder::TABLE, 'stock_order');
            $qbExistOrderError->where('stock_order.ord = orders.id');

            $qbExistOrderError->join(
                'stock_order',
                ProductStockEvent::TABLE,
                'stock_order_event',
                'stock_order_event.id = stock_order.event AND stock_order_event.status = :error'
            );

            $qbExistOrderError->join(
                'stock_order_event',
                ProductStock::TABLE,
                'stock_order_stock',
                'stock_order_stock.event = stock_order_event.id'
            );

            $qb->addSelect(sprintf('EXISTS(%s) AS order_error', $qbExistOrderError->getSQL()) );

            $qb->setParameter('error', new ProductStockStatus(new ProductStockStatus\ProductStockStatusError()), ProductStockStatus::TYPE);
        }
        else
        {
            $qb->addSelect('FALSE AS move_error');
            $qb->addSelect('FALSE AS order_error');
        }

        
        $qb->addOrderBy('order_event.created');

        $qb->setMaxResults(20);

        // dd($this->connection->prepare('EXPLAIN (ANALYZE)  '.$qb->getSQL())->executeQuery($qb->getParameters())->fetchAllAssociativeIndexed());

        return $this->paginator->fetchAllAssociative($qb);
    }
}
