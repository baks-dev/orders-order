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

namespace BaksDev\Orders\Order\Repository\ProductEventBasket;

use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductEventBasket implements ProductEventBasketInterface
{
    private EntityManagerInterface $entityManager;

    private TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    /** Mетод возвращает событие продукта  */
    public function getOneOrNullProductEvent(
        ProductEventUid         $event,
        ?ProductOfferUid        $offer,
        ?ProductVariationUid    $variation,
        ?ProductModificationUid $modification,

    ): ?ProductEventUid
    {
        $qb = $this->entityManager->createQueryBuilder();

        $Locale = new Locale($this->translator->getLocale());


        $select = sprintf('new %s(product.event, product.id, trans.name)', ProductEventUid::class);
        //$select = 'event';
        $qb->select($select);

        $qb->from(ProductEvent::class, 'event');
        $qb->where('event.id = :event');
        $qb->setParameter('event', $event, ProductEventUid::TYPE);

        $qb->join(Product::class, 'product', 'WITH', 'product.id = event.main');

        $qb->leftJoin(
            ProductTrans::class,
            'trans',
            'WITH',
            'trans.event = event.id AND trans.local = :local'
        );

        $qb->setParameter('local', $Locale, Locale::TYPE);


        /**
         * Торговое предложение
         */


        if ($offer) {

            $qb->join(
                ProductOffer::class,
                'offer',
                'WITH',
                'offer.event = event.id  AND offer.id = :offer'
            );

            $qb->setParameter('offer', $offer, ProductOfferUid::TYPE);


            if ($variation) {

                /**
                 * Множественный вариант торгового предложения
                 */

                $qb->join(
                    ProductVariation::class,
                    'variation',
                    'WITH',
                    'variation.offer = offer.id AND variation.id = :variation'
                );

                $qb->setParameter('variation', $variation, ProductVariationUid::TYPE);


                if ($modification) {
                    /**
                     * Модификация множественного варианта торгового предложения
                     */

                    $qb->join(
                        ProductModification::class,
                        'modification',
                        'WITH',
                        'modification.variation = variation.id AND modification.id = :modification'
                    );


                    $qb->setParameter('modification', $modification, ProductModificationUid::TYPE);
                }

            }


        }


        return $qb->getQuery()->getOneOrNullResult();
    }
}
