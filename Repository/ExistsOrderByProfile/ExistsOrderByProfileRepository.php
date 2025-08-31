<?php
/*
 * Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Repository\ExistsOrderByProfile;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;

final readonly class ExistsOrderByProfileRepository implements ExistsOrderByProfileInterface
{
    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод проверяет, имеются ли заказы у указанного профиля (а также опционально - с указанным продуктом и в
     * указанном статусе)
     */
    public function isExist(UserProfileUid $profile, ?OrderStatus $status = null, ?ProductUid $product = null): bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('invariable')
            ->from(OrderInvariable::class, 'invariable')
            ->where('invariable.profile = :profile')
            ->setParameter('profile', $profile);

        $dbal->join(
            'invariable',
            OrderEvent::class,
            'event',
            'event.id = invariable.event'
        );

        if($status instanceof OrderStatus)
        {
            $dbal
                ->where('event.status = :status')
                ->setParameter('status', $status, OrderStatus::TYPE);
        }

        if($product instanceof ProductUid)
        {
            $dbal->join(
                'event',
                OrderProduct::class,
                'order_product',
                'order_product.event = event.id'
            );

            $dbal
                ->join(
                    'order_product',
                    ProductEvent::class,
                    'product_event',
                    'product_event.id = order_product.product AND product_event.main = :product'
                )
                ->setParameter('product', $product, ProductUid::TYPE);
        }

        return $dbal->fetchExist();
    }
}