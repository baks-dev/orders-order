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

namespace BaksDev\Orders\Order\Repository\RelevantNewOrderByProduct;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Delivery\Entity\Delivery;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusPackage;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use InvalidArgumentException;


final class RelevantNewOrderByProductRepository implements RelevantNewOrderByProductInterface
{
    private DeliveryUid|false $delivery = false;

    private ProductEventUid|false $product = false;

    private ProductOfferUid|false $offer = false;

    private ProductVariationUid|false $variation = false;

    private ProductModificationUid|false $modification = false;

    private string $status = OrderStatusNew::class;

    private bool|null $access = null;

    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    public function forDelivery(Delivery|DeliveryUid|string $delivery): self
    {
        if(is_string($delivery))
        {
            $delivery = new DeliveryUid($delivery);
        }

        if($delivery instanceof Delivery)
        {
            $delivery = $delivery->getId();
        }

        $this->delivery = $delivery;

        return $this;
    }

    public function forProductEvent(ProductEvent|ProductEventUid|string $product): self
    {

        if(is_string($product))
        {
            $product = new ProductEventUid($product);
        }

        if($product instanceof ProductEvent)
        {
            $product = $product->getId();
        }

        $this->product = $product;

        return $this;
    }

    public function forOffer(ProductOffer|ProductOfferUid|string $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if(is_string($offer))
        {
            $offer = new ProductOfferUid($offer);
        }

        if($offer instanceof ProductOffer)
        {
            $offer = $offer->getId();
        }

        $this->offer = $offer;

        return $this;
    }

    public function forVariation(ProductVariation|ProductVariationUid|string|null|false $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if(is_string($variation))
        {
            $variation = new ProductVariationUid($variation);
        }

        if($variation instanceof ProductVariation)
        {
            $variation = $variation->getId();
        }

        $this->variation = $variation;

        return $this;
    }

    public function forModification(ProductModification|ProductModificationUid|string|null|false $modification): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if(is_string($modification))
        {
            $modification = new ProductModificationUid($modification);
        }

        if($modification instanceof ProductModification)
        {
            $modification = $modification->getId();
        }

        $this->modification = $modification;

        return $this;
    }

    public function onlyNewStatus(): self
    {
        $this->status = OrderStatusNew::class;

        return $this;
    }

    public function onlyPackageStatus(): self
    {
        $this->status = OrderStatusPackage::class;

        return $this;
    }

    /** Только заказы, которые требуют производства */
    public function filterProductAccess(): self
    {
        $this->access = true;

        return $this;
    }

    /** Только заказы, которые произведены и готовы к упаковке */
    public function filterProductNotAccess(): self
    {
        $this->access = false;

        return $this;
    }

    private function builder(): ORMQueryBuilder
    {
        if(false === $this->delivery)
        {
            throw new InvalidArgumentException('Invalid Argument Delivery');
        }

        if(false === $this->product)
        {
            throw new InvalidArgumentException('Invalid Argument Product');
        }


        $this->ORMQueryBuilder->clear();

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm->clear();

        $orm->from(Order::class, 'ord');

        $orm
            ->select('event')
            ->join(
                OrderEvent::class,
                'event',
                'WITH',
                'event.id = ord.event AND event.status = :status',
            )
            ->setParameter(
                'status',
                $this->status,
                OrderStatus::TYPE
            );

        $orm
            ->leftJoin(
                OrderUser::class,
                'usr',
                'WITH',
                'usr.event = ord.event',
            );

        $orm
            ->join(
                OrderDelivery::class,
                'delivery',
                'WITH',
                'delivery.usr = usr.id AND delivery.delivery = :delivery',
            )
            ->setParameter(
                'delivery',
                $this->delivery,
                DeliveryUid::TYPE
            );


        $orm
            ->join(
                OrderProduct::class,
                'product',
                'WITH',
                '
                    product.event = ord.event AND 
                    product.product = :product AND 
                    product.offer '.(false === $this->offer ? ' IS NULL' : ' = :offer').' AND
                    product.variation '.(false === $this->variation ? ' IS NULL' : ' = :variation').' AND
                    product.modification '.(false === $this->modification ? ' IS NULL' : ' = :modification').'
                '
            )
            ->setParameter(
                'product',
                $this->product,
                ProductEventUid::TYPE
            );


        /**
         * true - заказы, которым еще требуется производство, т.е. у которых total НЕ РАВЕН access
         * false - на заказы уже полностью имеется произведенная продукция, total РАВЕН access
         */
        if(is_bool($this->access))
        {
            $orm
                ->join(
                    OrderPrice::class,
                    'price',
                    'WITH',
                    '
                    price.product = product.id AND 
                    price.total '.($this->access === true ? '!=' : '=').' price.access
                ');
        }

        false === $this->offer ?: $orm->setParameter('offer', $this->offer, ProductOfferUid::TYPE);
        false === $this->variation ?: $orm->setParameter('variation', $this->variation, ProductVariationUid::TYPE);
        false === $this->modification ?: $orm->setParameter('modification', $this->modification, ProductModificationUid::TYPE);


        $orm->orderBy('delivery.deliveryDate');

        return $orm;
    }

    /**
     * Метод возвращает событие самого старого (более актуального) нового заказа
     * на указанный способ доставки и в котором имеется указанная продукция
     */
    public function find(): OrderEvent|false
    {
        $orm = $this->builder();

        $orm->setMaxResults(1);

        return $orm->getOneOrNullResult() ?: false;
    }

    /**
     * Метод возвращает все заказы самого старого (более актуального) нового заказа
     * на указанный способ доставки и в котором имеется указанная продукция
     */
    public function findAll(): array|false
    {
        $orm = $this->builder();

        return $orm->getResult() ?: false;
    }

}