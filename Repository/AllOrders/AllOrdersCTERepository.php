<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Repository\AllOrders;

use BaksDev\Auth\Email\Entity\Account;
use BaksDev\Auth\Email\Entity\Event\AccountEvent;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Delivery\Entity\Event\DeliveryEvent;
use BaksDev\Delivery\Entity\Price\DeliveryPrice;
use BaksDev\Delivery\Entity\Trans\DeliveryTrans;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Type\OrderStatus\OrderStatusDelivery;
use BaksDev\Field\Pack\Contact\Type\ContactField;
use BaksDev\Field\Pack\Phone\Type\PhoneField;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Event\Posting\OrderPosting;
use BaksDev\Orders\Order\Entity\Event\Project\OrderProject;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Entity\Services\OrderService;
use BaksDev\Orders\Order\Entity\Services\Price\OrderServicePrice;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\Delivery\Price\OrderDeliveryPrice;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Forms\OrderFilterInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusExtradition;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusMarketplace;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPackage;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusInterface;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Stock\Move\ProductStockMove;
use BaksDev\Products\Stocks\Entity\Stock\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\Entity\Total\ProductStockTotal;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus;
use BaksDev\Users\Profile\TypeProfile\Entity\Section\Fields\Trans\TypeProfileSectionFieldTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\Section\Fields\TypeProfileSectionField;
use BaksDev\Users\Profile\TypeProfile\Entity\Trans\TypeProfileTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\TypeProfile;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Discount\UserProfileDiscount;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Value\UserProfileValue;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class AllOrdersCTERepository implements AllOrdersInterface
{
    private array $analyze;

    private ?SearchDTO $search = null;

    private ?OrderStatus $status = null;

    private ?OrderFilterInterface $filter = null;

    private ?int $limit = null;

    private UserProfileUid|false $profile = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage,
    ) {}

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
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

    public function filter(OrderFilterInterface $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function forProfile(UserProfileUid|UserProfile $profile): self
    {
        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }


    //public function findPaginator(): PaginatorInterface
    // {

    public function findPaginator(): PaginatorInterface
    {

        /** Применяем статус если выбран в фильтре */
        if($this->filter instanceof OrderFilterInterface && $this->filter->getStatus())
        {
            $this->status = $this->filter->getStatus();
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)->bindLocal();

        /**
         * START cteSelect ===============================
         */

        $cteSelect = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $cteSelect
            ->select('orders.id AS order_id')
            ->from(Order::class, 'orders');

        $cteSelect
            //            ->addSelect('orders.event AS order_event')
            //            ->addSelect('order_invariable.number AS order_number')
            //            ->addSelect('order_event.danger AS order_danger')
            //            ->addSelect('order_event.created AS order_created')
            //            ->addSelect('order_event.status AS order_status')
            //            ->addSelect('orders_modify.mod_date AS orders_modify')
            //            ->addSelect('order_delivery.delivery_date AS delivery_date')
            ->join(
                'orders',
                OrderEvent::class,
                'order_event',
                'order_event.id = orders.event'
                .($this->status instanceof OrderStatus ? ' AND order_event.status = :status' : ''),
            );


        if($this->status instanceof OrderStatus)
        {
            $dbal
                ->setParameter(
                    key: 'status',
                    value: $this->status,
                    type: OrderStatus::TYPE,
                );
        }

        /** OrderInvariable */

        $cteSelect
            ->join(
                'orders',
                OrderInvariable::class,
                'order_invariable',
                'order_invariable.main = orders.id',
            );


        /** Если не выбраны все профили, то отобразить только для данного профиля/склада */
        if($this->filter?->getAll() === false || $this->profile instanceof UserProfileUid)
        {
            $whereExpression = (($this->status instanceof OrderStatus) && $this->status->equals(OrderStatusNew::class)
                ? ' (order_invariable.profile IS NULL OR order_invariable.profile = :profile)'
                : ' order_invariable.profile = :profile');

            $profile = ($this->profile instanceof UserProfileUid) ? $this->profile : $this->UserProfileTokenStorage->getProfile();

            $cteSelect->andWhere($whereExpression);

            $dbal->setParameter(
                key: 'profile',
                value: $profile,
                type: UserProfileUid::TYPE,
            );
        }


        $cteSelect
            ->leftJoin(
                'orders',
                OrderUser::class,
                'order_user',
                'order_user.event = orders.event',
            );

        $cteSelect
            ->leftJoin(
                'order_user',
                OrderDelivery::class,
                'order_delivery',
                'order_delivery.usr = order_user.id',
            );

        $cteSelect
            ->leftJoin(
                'orders',
                OrderModify::class,
                'orders_modify',
                'orders_modify.event = orders.event',
            );


        if(false === ($this->search instanceof SearchDTO) || true === empty($this->search->getQuery()))
        {
            $cteSelect->setMaxResults($this->paginator->getLimit());
        }

        $this->orderBy($cteSelect);


        /**
         * END cteSelect ===============================
         */

        $dbal
            ->select('orders.id AS order_id')
            ->addSelect('orders.event AS order_event');

        $dbal
            ->with('cte_orders', $cteSelect)
            ->from('cte_orders', 'cteSelect');

        $dbal
            ->join(
                'cteSelect',
                Order::class,
                'orders',
                'orders.id = cteSelect.order_id',
            );

        $dbal
            ->addSelect('order_invariable.number AS order_number')
            ->leftJoin(
                'orders',
                OrderInvariable::class,
                'order_invariable',
                'order_invariable.main = orders.id',
            );


        $dbal
            ->addSelect('orders_posting.value AS order_posting')
            ->leftJoin(
                'orders',
                OrderPosting::class,
                'orders_posting',
                'orders_posting.main = orders.id',
            );


        /** Информация о проекте */

        if(false === $dbal->bindProjectProfile())
        {
            $dbal->addSelect('FALSE AS is_other_project');
        }
        else
        {
            $dbal->addSelect('order_project.value != :'.$dbal::PROJECT_PROFILE_KEY.' AS is_other_project');
        }

        $dbal
            ->leftJoin(
                'orders',
                OrderProject::class,
                'order_project',
                'order_project.main = orders.id',
            );

        $dbal
            ->leftJoin(
                'order_project',
                UserProfile::class,
                'order_project_profile',
                'order_project_profile.id = order_project.value',
            );

        $dbal
            ->addSelect('order_project_personal.username AS project_profile_username')
            ->leftJoin(
                'order_project_profile',
                UserProfilePersonal::class,
                'order_project_personal',
                'order_project_personal.event = order_project_profile.event',
            );


        //        /** Если не выбраны все профили, то отобразить только для данного профиля/склада */
        //        if($this->filter?->getAll() === false || $this->profile instanceof UserProfileUid)
        //        {
        //            $whereExpression = (($this->status instanceof OrderStatus) && $this->status->equals(OrderStatusNew::class)
        //                ? ' (order_invariable.profile IS NULL OR order_invariable.profile = :profile)'
        //                : ' order_invariable.profile = :profile');
        //
        //            $dbal
        //                ->andWhere($whereExpression)
        //                ->setParameter(
        //                    key: 'profile',
        //                    value: ($this->profile instanceof UserProfileUid) ? $this->profile : $this->UserProfileTokenStorage->getProfile(),
        //                    type: UserProfileUid::TYPE,
        //                );
        //        }


        $dbal
            ->addSelect('order_event.status AS order_status')
            ->addSelect('order_event.created AS order_created')
            ->addSelect('order_event.comment AS order_comment')
            ->addSelect('order_event.danger AS order_danger')
            ->join(
                'orders',
                OrderEvent::class,
                'order_event',
                'order_event.id = orders.event',
            );

        $dbal
            ->addSelect('orders_modify.mod_date AS modify')
            ->leftJoin(
                'orders',
                OrderModify::class,
                'orders_modify',
                'orders_modify.event = orders.event',
            );

        /**
         * Идентификатор ответственного склада
         */

        $dbal
            ->leftJoin(
                'order_invariable',
                UserProfile::class,
                'stock_profile',
                'stock_profile.id = order_invariable.profile',
            );

        $dbal
            ->addSelect('stock_profile_personal.username AS stock_profile_username')
            ->addSelect('stock_profile_personal.location AS stock_profile_location')
            ->leftJoin(
                'stock_profile',
                UserProfilePersonal::class,
                'stock_profile_personal',
                'stock_profile_personal.event = stock_profile.event',
            );


        $dbal
            ->leftJoin(
                'orders',
                OrderProduct::class,
                'order_products',
                'order_products.event = orders.event',
            );

        $dbal
            ->addSelect('order_products_price.currency AS order_currency')
            ->leftJoin(
                'order_products',
                OrderPrice::class,
                'order_products_price',
                'order_products_price.product = order_products.id',
            );


        $dbal
            ->leftJoin(
                'orders',
                OrderUser::class,
                'order_user',
                'order_user.event = orders.event',
            );

        /** Доставка */
        $dbal
            ->addSelect('order_delivery.delivery_date AS delivery_date')
            ->leftJoin(
                'order_user',
                OrderDelivery::class,
                'order_delivery',
                'order_delivery.usr = order_user.id',
            );


        if($this->filter?->getDelivery())
        {
            $dbal
                ->join(
                    'order_delivery',
                    DeliveryEvent::class,
                    'delivery_event',
                    'delivery_event.id = order_delivery.event AND delivery_event.main = :delivery',
                )
                ->setParameter(
                    key: 'delivery',
                    value: $this->filter->getDelivery(),
                    type: DeliveryUid::TYPE,
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'order_delivery',
                    DeliveryEvent::class,
                    'delivery_event',
                    'delivery_event.id = order_delivery.event',
                );
        }

        $dbal
            ->addSelect('delivery_price.price AS delivery_price')
            ->leftJoin(
                'delivery_event',
                DeliveryPrice::class,
                'delivery_price',
                'delivery_price.event = delivery_event.id',
            );


        /** Стоимость доставки администратором */
        $dbal
            ->addSelect('order_delivery_price.price AS order_delivery_price')
            ->addSelect('order_delivery_price.currency AS order_delivery_currency')
            ->leftJoin(
                'order_delivery',
                OrderDeliveryPrice::class,
                'order_delivery_price',
                'order_delivery_price.delivery = order_delivery.id',
            );


        $dbal
            ->addSelect('delivery_trans.name AS delivery_name')
            ->leftJoin(
                'delivery_event',
                DeliveryTrans::class,
                'delivery_trans',
                'delivery_trans.event = delivery_event.id AND delivery_trans.local = :local',
            );


        /** Профиль пользователя (Клиент) */

        $dbal
            ->leftJoin(
                'order_user',
                UserProfileEvent::class,
                'user_profile',
                'user_profile.id = order_user.profile',
            );

        $dbal
            ->addSelect('user_profile_discount.value AS order_profile_discount')
            ->leftJoin(
                'user_profile',
                UserProfileDiscount::class,
                'user_profile_discount',
                'user_profile_discount.event = user_profile.id',
            );

        $dbal
            ->addSelect('user_profile_personal.username AS order_profile_username')
            ->leftJoin(
                'user_profile',
                UserProfilePersonal::class,
                'user_profile_personal',
                'user_profile_personal.event = user_profile.id',
            );


        $dbal
            ->leftJoin(
                'user_profile',
                UserProfileValue::class,
                'user_profile_value',
                'user_profile_value.event = user_profile.id',
            );


        /** Выбираем только контакт и номер телефон */
        $dbal
            ->leftJoin(
                'user_profile_value',
                TypeProfileSectionField::class,
                'type_section_field_client',
                '
                        type_section_field_client.id = user_profile_value.field AND
                        (type_section_field_client.type = :field_phone OR type_section_field_client.type = :field_contact)
                    ')
            ->setParameter(
                'field_phone',
                PhoneField::TYPE,
            )
            ->setParameter(
                'field_contact',
                ContactField::TYPE,
            );


        $dbal
            ->leftJoin(
                'user_profile',
                TypeProfile::class,
                'type_profile',
                'type_profile.id = user_profile.type',
            );


        /** Email-аккаунт пользователя */

        $dbal
            ->leftJoin(
                'order_user',
                Account::class,
                'account',
                'account.id = order_user.usr',
            );


        $dbal
            ->addSelect('account_event.email AS account_email')
            ->leftJoin(
                'account',
                AccountEvent::class,
                'account_event',
                'account_event.id = account.event',
            );

        /**
         * Название типа профиля (Заказа)
         */

        $dbal
            ->addSelect('type_profile_trans.name AS order_profile')
            ->leftJoin(
                'type_profile',
                TypeProfileTrans::class,
                'type_profile_trans',
                'type_profile_trans.event = type_profile.event AND type_profile_trans.local = :local',
            );

        $dbal
            ->leftJoin(
                'user_profile_value',
                TypeProfileSectionField::class,
                'type_profile_field',
                'type_profile_field.id = user_profile_value.field',
            );

        $dbal
            ->leftJoin(
                'type_profile_field',
                TypeProfileSectionFieldTrans::class,
                'type_profile_field_trans',
                'type_profile_field_trans.field = type_profile_field.id AND type_profile_field_trans.local = :local',
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
			AS order_user",
        );


        /**
         * @depricated данные ключи использовались в старой версии перемещения
         */
        $dbal->addSelect('FALSE AS order_move');
        $dbal->addSelect('FALSE AS move_error');
        $dbal->addSelect('FALSE AS order_error');

        /** Услуги */

        $dbal
            ->leftJoin(
                'orders',
                OrderService::class,
                'orders_service',
                'orders_service.event = orders.event',
            );

        $dbal
            ->leftJoin(
                'orders_service',
                OrderServicePrice::class,
                'orders_service_price',
                'orders_service_price.serv = orders_service.id',
            );


        $dbal->addSelect(
            "JSON_AGG
			( DISTINCT
		
					JSONB_BUILD_OBJECT
					(
						'service', orders_service_price.serv,
						'price', orders_service_price.price,
						'currency', orders_service_price.currency
					)
			)
			AS service_price",
        );

        $dbal->addSelect(
            "JSON_AGG
			( DISTINCT
		
					JSONB_BUILD_OBJECT
					(
						'product', order_products_price.product,

						'main', product_event.main,
						'offer', product_offer.const,
						'variation', product_variation.const,
						'modification', product_modification.const,
						
						'price', order_products_price.price,
						'total', order_products_price.total
					)
			)
			AS product_price",
        );

        if(
            class_exists(BaksDevProductsStocksBundle::class)
            && true === ($this->status instanceof OrderStatus)
            && $this->status->equals(OrderStatusNew::class)
        )
        {
            /** Получаем остаток и резерв на текущем складе */
            $dbal
                ->leftJoin(
                    'product_modification',
                    ProductStockTotal::class,
                    'stock',
                    '
                    
                    stock.profile = :profile 
                    AND stock.product = product_event.main
                    
                    AND
                        
                        CASE 
                            WHEN product_offer.const IS NOT NULL 
                            THEN stock.offer = product_offer.const
                            ELSE stock.offer IS NULL
                        END
                            
                    AND 
                    
                        CASE
                            WHEN product_variation.const IS NOT NULL 
                            THEN stock.variation = product_variation.const
                            ELSE stock.variation IS NULL
                        END
                        
                    AND
                    
                        CASE
                            WHEN product_modification.const IS NOT NULL 
                            THEN stock.modification = product_modification.const
                            ELSE stock.modification IS NULL
                        END
 
                ')
                ->setParameter(
                    key: 'profile',
                    value: ($this->profile instanceof UserProfileUid) ? $this->profile : $this->UserProfileTokenStorage->getProfile(),
                    type: UserProfileUid::TYPE,
                );


            $dbal->addSelect("JSON_AGG
			( DISTINCT
					JSONB_BUILD_OBJECT
					(
						'id', stock.id,
						
						'main', stock.product,
						'offer', stock.offer,
						'variation', stock.variation,
						'modification', stock.modification,
						
						'total', stock.total,
						'reserve', stock.reserve
					)
			) AS stocks");
        }
        else
        {
            $dbal->addSelect('NULL AS stocks');
        }

        $dbal->leftJoin(
            'order_products',
            ProductEvent::class,
            'product_event',
            'product_event.id = order_products.product',
        );

        $dbal->leftJoin(
            'product_event',
            ProductInfo::class,
            'product_info',
            'product_info.product = product_event.main',
        );

        $dbal
            ->leftJoin(
                'order_products',
                ProductOffer::class,
                'product_offer',
                'product_offer.id = order_products.offer',
            );


        $dbal
            ->leftJoin(
                'order_products',
                ProductVariation::class,
                'product_variation',
                'product_variation.id = order_products.variation',
            );

        $dbal
            ->leftJoin(
                'order_products',
                ProductModification::class,
                'product_modification',
                'product_modification.id = order_products.modification',
            );

        if($this->search instanceof SearchDTO && $this->search->getQuery())
        {
            if(
                preg_match('/^\d{3}\.\d{3}\.\d{3}\.\d{3}$/', $this->search->getQuery())
                || str_starts_with($this->search->getQuery(), 'o-')
                || str_starts_with($this->search->getQuery(), 'y-')
                || str_starts_with($this->search->getQuery(), 'w-')
            )
            {

                $dbal
                    ->createSearchQueryBuilder($this->search)
                    ->addSearchLike('orders_posting.value');
            }
            else
            {
                $dbal
                    ->createSearchQueryBuilder($this->search)
                    ->addSearchLike('orders_posting.value')
                    ->addSearchLike('user_profile_value.value')
                    ->addSearchLike('product_info.article')
                    ->addSearchLike('product_variation.article')
                    ->addSearchLike('product_modification.article');
            }
        }

        $this->orderBy($dbal);

        $dbal->allGroupByExclude();

        return $this->paginator->fetchAllHydrate(
            $dbal,
            AllOrdersResult::class,
            'orders-order'.($this->status instanceof OrderStatus ? '-'.$this->status : ''),
        );

        //return $dbal->fetchAllHydrate(AllOrdersResult::class);
    }

    /**
     * Общая группировка для подзапроса и основного запроса
     */
    private function orderBy(DBALQueryBuilder $dbal): DBALQueryBuilder
    {
        //        $dbal->orderBy('order_event.danger', 'DESC');
        //        $dbal->addOrderBy('order_delivery.delivery_date', 'ASC');
        //        $dbal->addOrderBy('orders.id', 'ASC');

        //        /** Список всех заказов без переданных статусов сортируем по дате изменения */
        //        if(false === ($this->status instanceof OrderStatus))
        //        {
        //            $dbal->orderBy('orders.event', 'DESC');
        //        }


        /** По умолчанию сортируем все по дате обновления */
        $dbal->orderBy('orders.event', 'DESC');

        if(true === ($this->status instanceof OrderStatus))
        {
            if(
                $this->status->equals(OrderStatusPackage::class)
                || $this->status->equals(OrderStatusDelivery::class)
                || $this->status->equals(OrderStatusExtradition::class)
            )
            {
                $dbal->orderBy('order_event.danger', 'DESC');
                $dbal->addOrderBy('order_delivery.delivery_date', 'ASC');
                $dbal->addOrderBy('orders.event', 'DESC');
            }

            if($this->status->equals(OrderStatusCompleted::class))
            {
                /** todo: после разделение на статус ПРЕД возвратов - поменять на DESC */
                $dbal->orderBy('order_event.danger', 'DESC');
                $dbal->addOrderBy('orders.event', 'DESC');
            }

            /**
             * Предвозвраты сортируем по дате обновления ASC
             */
            if($this->status->equals(OrderStatusMarketplace::class))
            {
                $dbal->orderBy('orders.event', 'ASC');
            }
        }

        return $dbal;
    }


}