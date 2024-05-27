<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Repository\ExistsOrderProduct;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusDraft;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class ExistsOrderProduct implements ExistsOrderProductInterface
{
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
    )
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }


    /**
     * Мотод проверяет, имеется ли такая продукция в быстром заказе
     */
    public function isExistsProductDraft(
        UserProfileUid $profile,
        ProductEventUid $product,
        ?ProductOfferUid $offer = null,
        ?ProductVariationUid $variation = null,
        ?ProductModificationUid $modification = null
    ): bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->select('event');
        $dbal->from(OrderEvent::class, 'event');

        $dbal
            ->andWhere('event.profile = :profile')
            ->setParameter('profile', $profile, UserProfileUid::TYPE);

        $dbal
            ->andWhere('event.status = :status')
            ->setParameter('status', OrderStatusDraft::STATUS);

        $dbal->join(
            'event',
            Order::class,
            'ord',
            'ord.event = event.id'
        );


        $dbal
            ->join(
                'event',
                OrderProduct::class,
                'product',
                'product.event = event.id'
            );

        $dbal
            ->andWhere('product.product = :product')
            ->setParameter('product', $product, ProductEventUid::TYPE);

        if($offer)
        {
            $dbal
                ->andWhere('product.offer = :offer')
                ->setParameter('offer', $offer, ProductOfferUid::TYPE);
        }

        if($offer)
        {
            $dbal
                ->andWhere('product.variation = :variation')
                ->setParameter('variation', $variation, ProductVariationUid::TYPE);
        }

        if($modification)
        {
            $dbal
                ->andWhere('product.modification = :modification')
                ->setParameter('modification', $modification, ProductModificationUid::TYPE);
        }

        return $dbal->fetchExist();
    }
}