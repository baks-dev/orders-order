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
use BaksDev\Products\Product\Entity as ProductEntity;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductOfferVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductOfferVariationModificationUid;
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

    public function getOneOrNullProductEvent(
        ProductEventUid $event,
        ?ProductOfferUid $offer,
        ?ProductOfferVariationUid $variation,
        ?ProductOfferVariationModificationUid $modification,
    ): ?ProductEventUid {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->setParameter('local', new Locale($this->translator->getLocale()), Locale::TYPE);

        $select = sprintf('new %s(product.event, product.id, trans.name)', ProductEventUid::class);

        $qb->select($select);

        $qb->from(ProductEntity\Event\ProductEvent::class, 'event');

        $qb->join(ProductEntity\Product::class, 'product', 'WITH', 'product.id = event.product');

        // Торговое предложение

        $qb->join(
            ProductEntity\Offers\ProductOffer::class,
            'offer',
            'WITH',
            'offer.event = event.id '.(null === $offer ? '' : 'AND offer.id = :offer')
        );

        if ($offer) {
            $qb->setParameter('offer', $offer, ProductOfferUid::TYPE);
        }

        // Множественный вариант торгового предложения

        $qb->leftJoin(
            ProductEntity\Offers\Variation\ProductOfferVariation::class,
            'variation',
            'WITH',
            'variation.offer = offer.id '.(null === $variation ? '' : 'AND variation.id = :variation')
        );

        if ($variation) {
            $qb->setParameter('variation', $variation, ProductOfferVariationUid::TYPE);
        }

        // Модификация множественного варианта торгового предложения

        $qb->leftJoin(
            ProductEntity\Offers\Variation\Modification\ProductOfferVariationModification::class,
            'modification',
            'WITH',
            'modification.variation = variation.id '.(null === $modification ? '' : 'AND modification.id = :modification')
        );

        if ($modification) {
            $qb->setParameter('modification', $modification, ProductOfferVariationModificationUid::TYPE);
        }

        $qb->leftJoin(
            ProductEntity\Trans\ProductTrans::class,
            'trans',
            'WITH',
            'trans.event = event.id AND trans.local = :local'
        );

        $qb->where('event.id = :event');
        $qb->setParameter('event', $event, ProductEventUid::TYPE);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
