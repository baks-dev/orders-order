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
 *
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Repository\Items\AllOrderProductItemConst;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Items\OrderProductItem;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Items\Const\OrderProductItemConst;
use BaksDev\Products\Sign\Entity\Event\ProductSignEvent;
use BaksDev\Products\Sign\Type\Status\ProductSignStatus;
use BaksDev\Products\Sign\Type\Status\ProductSignStatus\ProductSignStatusProcess;
use Generator;

final class AllOrderProductItemConstRepository implements AllOrderProductItemConstInterface
{
    private bool|null $sign = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder
    ) {}

    /** Единицы продукции, у которой есть Честный знак */
    public function withoutSign(): self
    {
        $this->sign = false;
        return $this;
    }

    /**
     * Возвращает множество констант единиц продукции конкретного заказа
     * @return Generator<int, OrderProductItemConst>|false
     */
    public function findAll(OrderUid $ord): Generator|false
    {
        $dbal = $this->builder($ord);

        $result = $dbal->fetchAllHydrate(OrderProductItemConst::class);

        return $result->valid() ? $result : false;
    }

    /** Метод для подсчета количества возвращаемого результата */
    public function count(OrderUid $ord): int
    {
        $dbal = $this->builder($ord);
        $result = $dbal->fetchAllAssociative();

        return empty($result) ? 0 : count($result);
    }

    private function builder(OrderUid $ord): DBALQueryBuilder
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(Order::class, 'orders');
        $dbal->distinct();

        /** Событие с проверкой на корень */
        $dbal
            ->join(
                'orders',
                OrderEvent::class,
                'event',
                '
                    event.id = orders.event AND
                    event.orders = :ord'
            )
            ->setParameter(
                key: 'ord',
                value: $ord,
                type: OrderUid::TYPE
            );

        /** Продукт */
        $dbal
            ->join(
                'event',
                OrderProduct::class,
                'product',
                'product.event = event.id'
            );

        /** Идентификаторы продукта в заказе */
        $dbal->addSelect(
            "
					JSONB_BUILD_OBJECT
					(
						'product', product.product,
						'offer', product.offer,
						'variation', product.variation,
						'modification', product.modification
					)
			
			AS params",
        );

        /** Единицы */
        $dbal
            ->addSelect('item.const as value')
            ->join(
                'event',
                OrderProductItem::class,
                'item',
                'item.product = product.id'
            );

        if(null !== $this->sign)
        {
            $dbal->setParameter(
                'status',
                new ProductSignStatus(ProductSignStatusProcess::class),
                ProductSignStatus::TYPE);

            $dbal->andWhereNotExists(
                ProductSignEvent::class,
                'product_sign_event',
                '
                    product_sign_event.ord = :ord AND
                    product_sign_event.status = :status AND
                    product_sign_event.product = item.const
                    '
            );

        }

        return $dbal;
    }
}
