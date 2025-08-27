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

namespace BaksDev\Orders\Order\Repository\ProductUserBasket\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;

/**
 * @group orders-order
 * @group orders-order-repo
 */
#[Group('orders-order')]
#[When(env: 'test')]
class ProductUserBasketTest extends KernelTestCase
{
    private static array|null $identifier = null;

    public static function setUpBeforeClass(): void
    {
        /** @var DBALQueryBuilder $qb */
        $qb = self::getContainer()->get(DBALQueryBuilder::class);
        $dbal = $qb->createQueryBuilder(self::class);

        $dbal
            ->select('product.event')
            ->from(Product::class, 'product')
            ->addSelect('offer.id AS offer')
            ->leftJoin('product', ProductOffer::class, 'offer', 'offer.event = product.event')
            ->addSelect('variation.id AS variation')
            ->leftJoin('offer', ProductVariation::class, 'variation', 'variation.offer = offer.id')
            ->addSelect('modification.id AS modification')
            ->leftJoin('variation', ProductModification::class, 'modification', 'modification.variation = variation.id')
            ->orderBy('RANDOM()')
            ->setMaxResults(1);

        self::$identifier = $dbal->fetchAssociative();
    }


    public function testUseCase(): void
    {
        if(empty(self::$identifier))
        {
            echo PHP_EOL.'Продукция не найдена :'.self::class.PHP_EOL;
            return;
        }

        /** @var ProductUserBasketInterface $ProductUserBasket */
        $ProductUserBasket = self::getContainer()->get(ProductUserBasketInterface::class);

        /** @var ProductUserBasketResult $ProductUserBasketResult */

        $ProductUserBasketResult = $ProductUserBasket
            ->forEvent(self::$identifier['event'])
            ->forOffer(self::$identifier['offer'])
            ->forVariation(self::$identifier['variation'])
            ->forModification(self::$identifier['modification'])
            ->find();

        if($ProductUserBasketResult instanceof ProductUserBasketResult)
        {

            self::assertInstanceOf(ProductUid::class, $ProductUserBasketResult->getProductId()); //: ProductUid
            self::assertInstanceOf(ProductEventUid::class, $ProductUserBasketResult->getProductEvent()); // ProductEventUid
            self::assertInstanceOf(ProductEventUid::class, $ProductUserBasketResult->getCurrentProductEvent()); // ProductEventUid
            self::assertInstanceOf(DateTimeImmutable::class, $ProductUserBasketResult->getProductActiveFrom()); // DateTimeImmutable

            self::assertIsString($ProductUserBasketResult->getProductName()); //: string
            self::assertIsString($ProductUserBasketResult->getProductArticle()); // string
            self::assertIsString($ProductUserBasketResult->getProductUrl()); // string

            // null|string

            if($ProductUserBasketResult->getProductOfferUid())
            {
                self::assertInstanceOf(ProductOfferUid::class, $ProductUserBasketResult->getProductOfferUid()); // ProductOfferUid|null
                self::assertInstanceOf(ProductOfferConst::class, $ProductUserBasketResult->getProductOfferConst()); // ProductOfferConst|null
                self::assertIsString($ProductUserBasketResult->getProductOfferValue()); // null|string
                self::assertIsString($ProductUserBasketResult->getProductOfferName());
            }
            else
            {
                self::assertNull($ProductUserBasketResult->getProductOfferUid()); // ProductOfferUid|null
                self::assertNull($ProductUserBasketResult->getProductOfferConst()); // ProductOfferConst|null
                self::assertNull($ProductUserBasketResult->getProductOfferValue()); // null|string
                self::assertNull($ProductUserBasketResult->getProductOfferName());
            }

            is_string($ProductUserBasketResult->getProductOfferPostfix()) ?
                self::assertIsString($ProductUserBasketResult->getProductOfferPostfix()) :
                self::assertNull($ProductUserBasketResult->getProductOfferPostfix()); // null|string

            is_string($ProductUserBasketResult->getProductOfferReference()) ?
                self::assertIsString($ProductUserBasketResult->getProductOfferReference()) :
                self::assertNull($ProductUserBasketResult->getProductOfferReference()); // null|string


            /** ProductVariation */
            if($ProductUserBasketResult->getProductVariationUid())
            {
                self::assertInstanceOf(ProductVariationUid::class, $ProductUserBasketResult->getProductVariationUid()); // ProductVariationUid|null
                self::assertInstanceOf(ProductVariationConst::class, $ProductUserBasketResult->getProductVariationConst()); // ProductVariationConst|null
                self::assertIsString($ProductUserBasketResult->getProductVariationValue()); // null|string
                self::assertIsString($ProductUserBasketResult->getProductVariationName()); // null|string
            }
            else
            {
                self::assertNull($ProductUserBasketResult->getProductVariationUid()); // ProductVariationUid|null
                self::assertNull($ProductUserBasketResult->getProductVariationConst()); // ProductVariationConst|null
                self::assertNull($ProductUserBasketResult->getProductVariationValue()); // null|string
                self::assertNull($ProductUserBasketResult->getProductVariationName()); // null|string
            }

            is_string($ProductUserBasketResult->getProductVariationPostfix()) ?
                self::assertIsString($ProductUserBasketResult->getProductVariationPostfix()) :
                self::assertNull($ProductUserBasketResult->getProductVariationPostfix()); // null|string


            is_string($ProductUserBasketResult->getProductVariationReference()) ?
                self::assertIsString($ProductUserBasketResult->getProductVariationReference()) :
                self::assertNull($ProductUserBasketResult->getProductVariationReference()); // null|string

            if($ProductUserBasketResult->getProductModificationUid())

            {
                self::assertInstanceOf(ProductModificationUid::class, $ProductUserBasketResult->getProductModificationUid()); // ProductModificationUid|null
                self::assertInstanceOf(ProductModificationConst::class, $ProductUserBasketResult->getProductModificationConst()); // ProductModificationConst|null
                self::assertIsString($ProductUserBasketResult->getProductModificationValue()); // null|string
                self::assertIsString($ProductUserBasketResult->getProductModificationName()); // null|string
            }
            else
            {
                self::assertNull($ProductUserBasketResult->getProductModificationUid()); // ProductModificationUid|null
                self::assertNull($ProductUserBasketResult->getProductModificationConst()); // ProductModificationConst|null
                self::assertNull($ProductUserBasketResult->getProductModificationValue()); // null|string
                self::assertNull($ProductUserBasketResult->getProductModificationName()); // null|string
            }

            is_string($ProductUserBasketResult->getProductModificationPostfix()) ?
                self::assertIsString($ProductUserBasketResult->getProductModificationPostfix()) :
                self::assertNull($ProductUserBasketResult->getProductModificationPostfix()); // null|string

            is_string($ProductUserBasketResult->getProductModificationReference()) ?
                self::assertIsString($ProductUserBasketResult->getProductModificationReference()) :
                self::assertNull($ProductUserBasketResult->getProductModificationReference()); // null|string


            self::assertIsString($ProductUserBasketResult->getProductImage()); // string
            self::assertIsString($ProductUserBasketResult->getProductImageExt()); // string
            self::assertIsBool($ProductUserBasketResult->getProductImageCdn()); // bool

            is_object($ProductUserBasketResult->getProductPrice()) ?
                self::assertInstanceOf(Money::class, $ProductUserBasketResult->getProductPrice()) :
                assertFalse($ProductUserBasketResult->getProductPrice()); // Money

            is_object($ProductUserBasketResult->getProductOldPrice()) ?
                self::assertInstanceOf(Money::class, $ProductUserBasketResult->getProductOldPrice()) :
                assertFalse($ProductUserBasketResult->getProductOldPrice()); // Money

            self::assertInstanceOf(Currency::class, $ProductUserBasketResult->getProductCurrency()); // Currency
            self::assertIsInt($ProductUserBasketResult->getProductQuantity()); // int

            self::assertIsString($ProductUserBasketResult->getCategoryName()); // string
            self::assertIsString($ProductUserBasketResult->getCategoryUrl()); // string
            self::assertIsInt($ProductUserBasketResult->getCategoryMinimal()); // int
            self::assertIsInt($ProductUserBasketResult->getCategoryInput()); // int
            self::assertIsInt($ProductUserBasketResult->getCategoryThreshold()); // int
            self::assertIsArray($ProductUserBasketResult->getCategorySectionField()); // array

            return;
        }

        self::assertFalse($ProductUserBasketResult);
    }

}