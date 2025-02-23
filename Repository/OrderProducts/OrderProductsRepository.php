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

namespace BaksDev\Orders\Order\Repository\OrderProducts;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;
use Generator;
use InvalidArgumentException;

final class OrderProductsRepository implements OrderProductsInterface
{
    private OrderUid|false $order = false;

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

    /**
     * Метод возвращает продукцию в заказе (идентификаторы)
     */
    public function findAllProducts(): Generator|false
    {
        if(false === $this->order)
        {
            throw new InvalidArgumentException('Invalid Argument Order');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('ord.id AS order_id')
            ->addSelect('ord.event AS order_event')
            ->from(Order::class, 'ord')
            ->where('ord.id = :order')
            ->setParameter('order', $this->order, OrderUid::TYPE);

        $dbal
            ->join(
                'ord',
                OrderProduct::class,
                'products',
                'products.event = ord.event'
            );

        $dbal
            ->addSelect('product_event.main AS product_id')
            ->addSelect('product_event.id AS product_event')
            ->leftJoin(
                'products',
                ProductEvent::class,
                'product_event',
                'product_event.id = products.product'
            );

        $dbal
            ->addSelect('product_offer.id AS product_offer')
            ->addSelect('product_offer.const AS product_offer_const')
            ->addSelect('product_offer.value AS product_offer_value')
            ->leftJoin(
                'products',
                ProductOffer::class,
                'product_offer',
                'product_offer.id = products.offer'
            );

        $dbal
            ->addSelect('product_variation.id AS product_variation')
            ->addSelect('product_variation.const AS product_variation_const')
            ->addSelect('product_variation.value AS product_variation_value')
            ->leftJoin(
                'products',
                ProductVariation::class,
                'product_variation',
                'product_variation.id = products.variation'
            );

        $dbal
            ->addSelect('product_modification.id AS product_modification')
            ->addSelect('product_modification.const AS product_modification_const')
            ->addSelect('product_modification.value AS product_modification_value')
            ->leftJoin(
                'products',
                ProductModification::class,
                'product_modification',
                'product_variation.id = products.modification'
            );

        $result = $dbal
            ->enableCache('orders-order', 3600)
            ->fetchAllHydrate(OrderProductResultDTO::class);

        return $result->valid() ? $result : false;
    }
}
