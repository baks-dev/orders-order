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

namespace BaksDev\Orders\Order\Repository\AllOrdersReport\Tests;

use BaksDev\Orders\Order\Repository\AllOrdersReport\AllOrdersReportInterface;
use BaksDev\Orders\Order\Repository\AllOrdersReport\AllOrdersReportResult;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Tests\ProductsProductNewTest;
use BaksDev\Reference\Money\Type\Money;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group orders-order
 * @group orders-order-repository
 * @depends BaksDev\Orders\Order\UseCase\Admin\Status\Tests\OrderStatusCompleteTest::class
 */
#[When(env: 'test')]
final class AllOrdersReportRepositoryTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        ProductsProductNewTest::setUpBeforeClass();
        new ProductsProductNewTest()->testUseCase();
    }

    public function testFind(): void
    {
        /** @var AllOrdersReportInterface $allProductsOrdersReportRepository */
        $allProductsOrdersReportRepository = self::getContainer()->get(AllOrdersReportInterface::class);

        $result = $allProductsOrdersReportRepository
            ->date(new DateTimeImmutable())
            ->findAll();

        if(false === $result)
        {
            self::assertFalse(false);
            return;
        }

        /** @var AllOrdersReportResult $AllOrdersReportResult */
        foreach($result as $AllOrdersReportResult)
        {
            self::assertInstanceOf(AllOrdersReportResult::class, $AllOrdersReportResult);

            self::assertInstanceOf(DateTimeImmutable::class, $AllOrdersReportResult->getDate()); //: DateTimeImmutable
            self::assertIsString($AllOrdersReportResult->getNumber()); //: string
            self::assertInstanceOf(Money::class, $AllOrdersReportResult->getProductPrice()); //: Money;

            self::assertInstanceOf(Money::class, $AllOrdersReportResult->getOrderPrice()); //: Money;
            self::assertInstanceOf(Money::class, $AllOrdersReportResult->getMoney()); //: Money;
            self::assertInstanceOf(Money::class, $AllOrdersReportResult->getProfit()); //: Money;

            self::assertIsInt($AllOrdersReportResult->getTotal());

            self::assertTrue((is_string($AllOrdersReportResult->getProductName()) || is_null($AllOrdersReportResult->getProductName())));
            self::assertTrue((is_string($AllOrdersReportResult->getProductArticle()) || is_null($AllOrdersReportResult->getProductArticle())));

            self::assertTrue((is_string($AllOrdersReportResult->getProductOfferValue()) || is_null($AllOrdersReportResult->getProductOfferValue())));
            self::assertTrue((is_string($AllOrdersReportResult->getProductOfferPostfix()) || is_null($AllOrdersReportResult->getProductOfferPostfix())));
            self::assertTrue((is_string($AllOrdersReportResult->getProductOfferReference()) || is_null($AllOrdersReportResult->getProductOfferReference())));

            self::assertTrue((is_string($AllOrdersReportResult->getProductVariationValue()) || is_null($AllOrdersReportResult->getProductVariationValue())));
            self::assertTrue((is_string($AllOrdersReportResult->getProductVariationPostfix()) || is_null($AllOrdersReportResult->getProductVariationPostfix())));
            self::assertTrue((is_string($AllOrdersReportResult->getProductVariationReference()) || is_null($AllOrdersReportResult->getProductVariationReference())));

            self::assertTrue((is_string($AllOrdersReportResult->getProductModificationValue()) || is_null($AllOrdersReportResult->getProductModificationValue())));
            self::assertTrue((is_string($AllOrdersReportResult->getProductModificationPostfix()) || is_null($AllOrdersReportResult->getProductModificationPostfix())));
            self::assertTrue((is_string($AllOrdersReportResult->getProductModificationReference()) || is_null($AllOrdersReportResult->getProductModificationReference())));

            self::assertTrue((is_string($AllOrdersReportResult->getDeliveryName()) || is_null($AllOrdersReportResult->getDeliveryName())));
            self::assertInstanceOf(Money::class, $AllOrdersReportResult->getDeliveryPrice());

            break;
        }
    }
}