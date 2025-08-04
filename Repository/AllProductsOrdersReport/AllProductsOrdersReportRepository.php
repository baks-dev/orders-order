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

namespace BaksDev\Orders\Order\Repository\AllProductsOrdersReport;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\ProductInvariable;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Generator;

final class AllProductsOrdersReportRepository implements AllProductsOrdersReportInterface
{
    private DateTimeImmutable $from;

    private DateTimeImmutable $to;

    private UserProfileUid|false $profile = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}


    public function from(DateTimeImmutable $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function to(DateTimeImmutable $to): self
    {
        $this->to = $to;

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

    /**
     * Метод возвращает информацию о заказах по продуктам
     */
    public function findAll(): Generator|false
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal->from(Order::class, "orders");

        $dbal->join(
            "orders",
            OrderInvariable::class,
            "order_invariable",
            "order_invariable.main = orders.id"
            .($this->profile instanceof UserProfileUid ? ' AND order_invariable.profile = :profile' : ''),
        );

        if($this->profile instanceof UserProfileUid)
        {
            $dbal->setParameter(
                key: 'profile',
                value: $this->profile,
                type: UserProfileUid::TYPE,
            );
        }

        $dbal->join(
            "orders",
            OrderEvent::class,
            "orders_event",
            "
                    orders_event.id = orders.event AND
                    orders_event.status = 'completed'
                ",
        );


        $dbal->join(
            "orders",
            OrderProduct::class,
            "orders_product",
            "orders_product.event = orders.event",
        );


        $dbal
            ->select("SUM(orders_price.total) AS total")
            ->addSelect("SUM(orders_price.total * orders_price.price) AS money")
            ->join(
                "orders_product",
                OrderPrice::class,
                "orders_price",
                "orders_price.product = orders_product.id",
            );

        $dbal
            ->join(
                "orders",
                OrderModify::class,
                "orders_modify",
                "
                    orders_modify.event = orders.event AND
                    DATE(orders_modify.mod_date) >= :from AND
                    DATE(orders_modify.mod_date) <= :to
                ",
            )
            ->setParameter(
                key: "from",
                value: $this->from,
                type: Types::DATE_IMMUTABLE,
            )
            ->setParameter(
                key: "to",
                value: $this->to,
                type: Types::DATE_IMMUTABLE,
            );


        $dbal
            ->leftJoin(
                "orders_product",
                ProductEvent::class,
                "product_event",
                "product_event.id = orders_product.product",
            );

        $dbal->leftJoin(
            "orders_product",
            ProductInfo::class,
            "product_info",
            "product_info.event = product_event.id",
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

        $dbal
            ->addSelect("product_trans.name AS product_name")
            ->leftJoin(
                "orders_product",
                ProductTrans::class,
                "product_trans",
                "product_trans.event = product_event.id AND product_trans.local = :local",
            );

        /**
         * Product Invariable
         */
        $dbal
            ->leftJoin(
                'product_modification',
                ProductInvariable::class,
                'product_invariable',
                '
                    product_invariable.product = orders_product.product AND
                    (
                        (product_offer.const IS NOT NULL AND product_invariable.offer = product_offer.const) OR
                        (product_offer.const IS NULL AND product_invariable.offer IS NULL)
                    )
                    AND
                    (
                        (product_variation.const IS NOT NULL AND product_invariable.variation = product_variation.const) OR
                        (product_variation.const IS NULL AND product_invariable.variation IS NULL)
                    )
                   AND
                   (
                        (product_modification.const IS NOT NULL AND product_invariable.modification = product_modification.const) OR
                        (product_modification.const IS NULL AND product_invariable.modification IS NULL)
                   )
            ');

        $dbal->allGroupByExclude();

        $dbal
            ->orderBy("product_trans.name")
            ->addOrderBy("SUM(orders_price.total)", 'DESC')
            ->addOrderBy("product_offer.value")
            ->addOrderBy("product_variation.value")
            ->addOrderBy("product_modification.value");

        $result = $dbal->fetchAllHydrate(AllProductsOrdersReportResult::class);

        return $result->valid() ? $result : false;

    }

}