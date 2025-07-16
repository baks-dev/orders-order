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

namespace BaksDev\Orders\Order\Repository\AllOrdersReport;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Delivery\Entity\Event\DeliveryEvent;
use BaksDev\Delivery\Entity\Trans\DeliveryTrans;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\Delivery\Price\OrderDeliveryPrice;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Discount\UserProfileDiscount;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\Status\UserProfileStatusActive;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\UserProfileStatus;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Generator;
use InvalidArgumentException;

final class AllOrdersReportRepository implements AllOrdersReportInterface
{
    private DateTimeImmutable|false $date = false;

    public function date(DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает все необходимые данные для составления отчета по заказам за определенную дату
     * @return Generator{int, AllOrdersReportResult}|false
     */
    public function findAll(): Generator|false
    {
        if(false === ($this->date instanceof DateTimeImmutable))
        {
            throw new InvalidArgumentException('Invalid Argument Date');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal->from(Order::class, "orders");

        $dbal->join(
            'orders',
            OrderEvent::class,
            "orders_event",
            "
                    orders_event.id = orders.event AND
                    orders_event.status = 'completed'
                "
        );

        $dbal
            ->addSelect("orders_modify.mod_date AS mod_date")
            ->join(
                "orders",
                OrderModify::class,
                "orders_modify",
                "orders_modify.event = orders.event AND DATE(orders_modify.mod_date) = :date",
            )
            ->setParameter(
                key: "date",
                value: $this->date,
                type: Types::DATE_IMMUTABLE,
            );

        $dbal
            ->addSelect("orders_invariable.number AS number")
            ->join(
                "orders",
                OrderInvariable::class,
                "orders_invariable",
                "orders_invariable.main = orders.id",
            );

        $dbal->join(
            "orders",
            OrderProduct::class,
            "orders_product",
            "orders_product.event = orders.event",
        );

        $dbal
            ->addSelect("orders_price.price AS order_price")
            ->addSelect("orders_price.total")
            ->addSelect("(orders_price.total * orders_price.price) AS money")
            ->join(
                "orders_product",
                OrderPrice::class,
                "orders_price",
                "orders_price.product = orders_product.id",
            );


        $dbal->leftJoin(
            "orders",
            OrderUser::class,
            "orders_user",
            "orders_user.event = orders.event",
        );

        $dbal->leftJoin(
            "orders_user",
            OrderDelivery::class,
            "orders_delivery",
            "orders_delivery.usr = orders_user.id",
        );

        $dbal
            ->addSelect("orders_delivery_price.price AS delivery_price")
            ->leftJoin(
                "orders_delivery",
                OrderDeliveryPrice::class,
                "orders_delivery_price",
                "orders_delivery_price.delivery =  orders_delivery.id",
            );

        $dbal->leftJoin(
            "orders_delivery",
            DeliveryEvent::class,
            "delivery_event",
            "delivery_event.id = orders_delivery.event",
        );

        $dbal
            ->addSelect("delivery_trans.name AS delivery_name")
            ->leftJoin(
                "delivery_event",
                DeliveryTrans::class,
                "delivery_trans",
                "delivery_trans.event = delivery_event.id AND delivery_trans.local = :local",
            );

        $dbal->join(
            "orders_product",
            ProductEvent::class,
            "product_event",
            "product_event.id = orders_product.product",
        );

        $dbal->leftJoin(
            "orders_product",
            ProductInfo::class,
            "product_info",
            "product_info.product = product_event.main",
        );

        $dbal
            ->addSelect("product_offer.value AS product_offer_value")
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->leftJoin(
                "orders_product",
                ProductOffer::class,
                "product_offer",
                "product_offer.id = orders_product.offer",
            );

        /** Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference as product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer',
            );

        $dbal
            ->addSelect("product_variation.value AS product_variation_value")
            ->addSelect('product_variation.postfix as product_variation_postfix')
            ->leftJoin(
                "orders_product",
                ProductVariation::class,
                "product_variation",
                "product_variation.id = orders_product.variation",
            );

        /** Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_offer_variation.reference as product_variation_reference')
            ->leftJoin(
                'product_variation',
                CategoryProductVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = product_variation.category_variation',
            );


        $dbal
            ->addSelect("product_modification.value AS product_modification_value")
            ->addSelect('product_modification.postfix as product_modification_postfix')
            ->leftJoin(
                "orders_product",
                ProductModification::class,
                "product_modification",
                "product_modification.id = orders_product.modification",
            );

        /** Получаем тип модификации */
        $dbal
            ->addSelect('category_offer_modification.reference as product_modification_reference')
            ->leftJoin(
                'product_modification',
                CategoryProductModification::class,
                'category_offer_modification',
                'category_offer_modification.id = product_modification.category_modification',
            );

        $dbal->addSelect('
			COALESCE(
			   product_modification.article,
			   product_variation.article,
			   product_offer.article,
			   product_info.article
			) AS product_article
		');

        $dbal->addSelect("
                   COALESCE(
				   product_modification_price.price,
				   product_variation_price.price,
				   product_offer_price.price,
				   product_event_price.price
				) AS product_price
			");


        $dbal->addSelect("
                CASE
                    WHEN product_modification_price.price IS NOT NULL
                    THEN (orders_price.price - product_modification_price.price) * orders_price.total
                    
                    WHEN product_variation_price.price IS NOT NULL
                    THEN (orders_price.price - product_variation_price.price) * orders_price.total
                    
                    WHEN product_offer_price.price IS NOT NULL
                    THEN (orders_price.price - product_offer_price.price) * orders_price.total
                    
                    WHEN product_event_price.price IS NOT NULL
                    THEN (orders_price.price - product_event_price.price) * orders_price.total
                    
                    ELSE NULL
                END AS profit
            ");

        $dbal->leftJoin(
            "orders_product",
            ProductPrice::class,
            "product_event_price",
            "product_event_price.event = orders_product.product",
        );

        $dbal->leftJoin(
            "product_offer",
            ProductOfferPrice::class,
            "product_offer_price",
            "product_offer_price.offer = product_offer.id",
        );

        $dbal->leftJoin(
            "product_variation",
            ProductVariationPrice::class,
            "product_variation_price",
            "product_variation_price.variation = product_variation.id",
        );

        $dbal->leftJoin(
            "product_modification",
            ProductModificationPrice::class,
            "product_modification_price",
            "product_modification_price.modification = product_modification.id",
        );

        $dbal
            ->addSelect("product_trans.name AS product_name")
            ->leftJoin(
                "orders_product",
                ProductTrans::class,
                "product_trans",
                "product_trans.event = product_event.id AND product_trans.local = :local",
            );


        /** Персональная скидка из профиля авторизованного пользователя */
        if(true === $dbal->bindCurrentProfile())
        {

            $dbal
                ->join(
                    'orders',
                    UserProfile::class,
                    'current_profile',
                    '
                        current_profile.id = :'.$dbal::CURRENT_PROFILE_KEY,
                );

            $dbal
                ->addSelect('current_profile_discount.value AS profile_discount')
                ->leftJoin(
                    'current_profile',
                    UserProfileDiscount::class,
                    'current_profile_discount',
                    '
                        current_profile_discount.event = current_profile.event
                        ',
                );
        }

        /** Общая скидка (наценка) из профиля магазина */
        if(true === $dbal->bindProjectProfile())
        {

            $dbal
                ->join(
                    'orders',
                    UserProfile::class,
                    'project_profile',
                    '
                        project_profile.id = :'.$dbal::PROJECT_PROFILE_KEY,
                );

            $dbal
                ->addSelect('project_profile_discount.value AS project_discount')
                ->leftJoin(
                    'project_profile',
                    UserProfileDiscount::class,
                    'project_profile_discount',
                    '
                        project_profile_discount.event = project_profile.event',
                );
        }


        $dbal
            ->orderBy("delivery_trans.name")
            ->addOrderBy("orders_modify.mod_date");

        $result = $dbal->fetchAllHydrate(AllOrdersReportResult::class);

        return $result->valid() ? $result : false;
    }

    public function toArray(): array|false
    {
        $result = $this->findAll();

        if(false === $result || false === $result->valid())
        {
            return false;
        }

        return iterator_to_array($result);
    }
}