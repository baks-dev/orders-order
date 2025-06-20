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

namespace BaksDev\Orders\Order\Repository\ProductTotalInOrders;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPhone;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusUnpaid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class ProductTotalInOrdersRepository implements ProductTotalInOrdersInterface
{
    private UserProfileUid|false $profile = false;

    private ProductUid|false $product = false;

    private ProductOfferConst|false $offerConst = false;

    private ProductVariationConst|false $variationConst = false;

    private ProductModificationConst|false $modificationConst = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /** Фильтр по профилю */
    public function onProfile(UserProfileUid $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    /** Идентификатор продукта */
    public function onProduct(Product|ProductUid $product): self
    {
        if($product instanceof Product)
        {
            $product = $product->getId();
        }

        $this->product = $product;
        return $this;
    }

    /** Уникальная константа Offer */
    public function onOfferConst(ProductOfferConst|null $offerConst): self
    {
        if(is_null($offerConst))
        {
            $this->offerConst = false;
            return $this;
        }

        $this->offerConst = $offerConst;
        return $this;
    }

    /** Уникальная константа Variation */
    public function onVariationConst(ProductVariationConst|null $variationConst): self
    {
        if(is_null($variationConst))
        {
            $this->variationConst = false;
            return $this;
        }

        $this->variationConst = $variationConst;
        return $this;
    }

    /** Уникальная константа Modification */
    public function onModificationConst(ProductModificationConst|null $modificationConst): self
    {
        if(is_null($modificationConst))
        {
            $this->modificationConst = false;
            return $this;
        }

        $this->modificationConst = $modificationConst;
        return $this;
    }

    /**
     * Возвращает количество продуктов, у которых есть заказы в статусах: new, phone, unpaid
     */
    public function findTotal(): int|false
    {
        $builder = $this->builder();

        $result = $builder->fetchAssociative();

        if(false === $result)
        {
            return false;
        }

        if(false === isset($result['order_products_total']))
        {
            return false;
        }

        return $result['order_products_total'];
    }

    private function builder(): DBALQueryBuilder
    {
        if(false === ($this->product instanceof ProductUid))
        {
            throw new InvalidArgumentException('Не передан обязательный аргумент запроса: $this->product');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(Product::class, 'product')
            ->where('product.id = :product')
            ->setParameter('product', $this->product, ProductUid::TYPE);


        /** Все события продукта */
        $dbal
            ->leftJoin(
                'product',
                ProductEvent::class,
                'product_event',
                'product_event.main = product.id',
            );

        /** Offer */
        if($this->offerConst instanceof ProductOfferConst)
        {
            $dbal
                ->join(
                    'product',
                    ProductOffer::class,
                    'product_offer',
                    '
                        product_offer.event = product_event.id 
                        AND product_offer.const = :offerConst',
                )
                ->setParameter(
                    'offerConst',
                    $this->offerConst,
                    ProductOfferConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product',
                    ProductOffer::class,
                    'product_offer',
                    'product_offer.event = product_event.id'
                );
        }

        /** Variation */
        if($this->variationConst instanceof ProductVariationConst)
        {

            $dbal
                ->join(
                    'product_offer',
                    ProductVariation::class,
                    'product_variation',
                    '
                            product_variation.offer = product_offer.id 
                            AND product_variation.const = :variationConst',
                )
                ->setParameter(
                    'variationConst',
                    $this->variationConst,
                    ProductVariationConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_offer',
                    ProductVariation::class,
                    'product_variation',
                    'product_variation.offer = product_offer.id'
                );
        }

        /** Modification */
        if($this->modificationConst instanceof ProductModificationConst)
        {
            $dbal
                ->join(
                    'product_variation',
                    ProductModification::class,
                    'product_modification',
                    '
                        product_modification.variation = product_variation.id 
                        AND product_modification.const = :modificationConst',
                )
                ->setParameter(
                    'modificationConst',
                    $this->modificationConst,
                    ProductModificationConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_variation',
                    ProductModification::class,
                    'product_modification',
                    'product_modification.variation = product_variation.id'
                );
        }

        /**
         * Order
         */

        /** Все заказы по событию продукта и идентификаторам offer, variation, modification */
        $dbal
            ->join(
                'product_modification',
                OrderProduct::class,
                'order_product',
                '
                    order_product.product = product_event.id 
                    AND 
                    (
                        (order_product.offer = product_offer.id AND order_product.variation = product_variation.id AND order_product.modification = product_modification.id) 
                        
                        OR
                        (order_product.offer = product_offer.id AND order_product.variation = product_variation.id AND product_modification.id IS NULL)
                        
                        OR
                        (order_product.offer = product_offer.id AND product_variation.id IS NULL AND product_modification.id IS NULL)
                        
                        OR
                        (product_offer.id IS NULL AND product_variation.id IS NULL AND product_modification.id IS NULL)
                    )
                '
            );

        /** Количество продуктов в заказах */
        $dbal
            ->addSelect('SUM(order_product_price.total) AS order_products_total')
            ->leftJoin(
                'order_product',
                OrderPrice::class,
                'order_product_price',
                'order_product_price.product = order_product.id'
            );

        /** Профиль (склад, магазин) */
        if($this->profile instanceof UserProfileUid)
        {

            $dbal
                ->join(
                    'order_product',
                    OrderInvariable::class,
                    'order_invariable',
                    '
                        order_invariable.event = order_product.event 
                        AND
                        (
                            order_invariable.profile IS NULL 
                            OR order_invariable.profile = :profile
                        )
                    '
                )
                ->setParameter(key: 'profile', value: $this->profile, type: UserProfileUid::TYPE);
        }
        else
        {
            $dbal
                ->join(
                    'order_product',
                    OrderInvariable::class,
                    'order_invariable',
                    '
                        order_invariable.event = order_product.event
                        AND order_invariable.profile IS NULL
                    '
                );
        }

        /** Статусы заказа */
        $dbal
            ->join(
                'order_invariable',
                OrderEvent::class,
                'order_event',
                '
                    order_event.id = order_invariable.event 
                    AND
                    (
                        order_event.status = :new
                        OR order_event.status = :phone 
                        OR order_event.status = :unpaid 
                    )
                '
            )
            ->setParameter('new', OrderStatusNew::STATUS, OrderStatus::TYPE)
            ->setParameter('phone', OrderStatusPhone::STATUS, OrderStatus::TYPE)
            ->setParameter('unpaid', OrderStatusUnpaid::STATUS, OrderStatus::TYPE);

        $dbal->allGroupByExclude();

        return $dbal;
    }
}
