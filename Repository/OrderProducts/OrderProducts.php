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

declare(strict_types=1);

namespace BaksDev\Orders\Order\Repository\OrderProducts;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;

final class OrderProducts implements OrderProductsInterface
{
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
    )
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /**
     * Метод возвращает продукцию в заказе
     */
    public function fetchAllOrderProducts(OrderUid $order): ?array
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->addSelect('ord.id AS oder_id')
            ->addSelect('ord.event AS oder_event')
            ->from(Order::TABLE, 'ord')
            ->where('ord.id = :order')
            ->setParameter('order', $order, OrderUid::TYPE)
        ;

        $qb
            ->addSelect('products.product AS product_event')
            ->addSelect('products.offer AS product_offer')
            ->addSelect('products.variation AS product_variation')
            ->addSelect('products.modification AS product_modification')
            ->join(
            'ord',
            OrderProduct::TABLE,
            'products',
            'products.event = ord.event'
        );

        $qb
            ->addSelect('product_event.main AS product_id')

            ->leftJoin(
                'products',
                ProductEvent::TABLE,
                'product_event',
                'product_event.id = products.product'
            );



        if(class_exists(WbProductCard::class))
        {
            $qb
                ->leftJoin(
                    'products',
                    ProductVariation::TABLE,
                    'product_variation',
                    'product_variation.id = products.variation'
                );
            $qb

                ->addSelect('barcode')
                ->leftJoin(
                    'product_variation',
                    WbProductCardVariation::TABLE,
                    'wb_card_variation',
                    'wb_card_variation.variation = product_variation.const'
                );
        }

        return $qb
            ->enableCache('Orders', 3600)
            ->fetchAllAssociative();
    }
}