<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Repository\ProductTotalInOrders\Tests;

use BaksDev\Orders\Order\Repository\ProductTotalInOrders\ProductTotalInOrdersInterface;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

// @TODO зависимость на тестовые продукты
#[When(env: 'test')]
class ProductTotalInOrdersRepositoryTest extends KernelTestCase
{
    public static array $products;

    public static function setUpBeforeClass(): void
    {
        self::$products = [
            // Product
            [
                'productName' => 'EPSON-L8050',
                'productId' => new ProductUid("0195cc92-93af-740e-a6b3-23f7d9c02a9f"),
                'offerConst' => null,
                'variationConst' => null,
                'modificationConst' => null,
            ],
            // Product, Offer
            [
                'productName' => 'PrintKit-HPC480-3',
                'productId' => new ProductUid("0195cec7-ee6b-72a8-973b-9414568b7e05"),
                'offerConst' => new ProductOfferConst("0195cec7-ee2f-7499-8fa8-32feac2fb17b"),
                'variationConst' => null,
                'modificationConst' => null,
            ],
            // Product, Offer, Variation
            [
                'productName' => 'Agalsea',
                'productId' => new ProductUid("0195cef2-7fce-7388-8184-658c7bc4db43"),
                'offerConst' => new ProductOfferConst("0195d1e6-2e63-74ee-bef3-bd45cce5c8af"),
                'variationConst' => new ProductVariationConst("0195d1e6-2dfa-7ec8-bbc4-ab5b5e985f4f"),
                'modificationConst' => null,
            ],
            // Product, Offer, Variation, Modification
            [
                'productName' => 'Triangle TR258-16-255-65-109T',
                'productId' => new ProductUid("018b1fbc-dc5a-7328-a30c-683e7e6d172a"),
                'offerConst' => new ProductOfferConst("018b1fbc-dbae-75ac-80cb-2b1626d6a9ce"),
                'variationConst' => new ProductVariationConst("018b1fbc-dbce-78a3-9a04-1308bfca35d4"),
                'modificationConst' => new ProductModificationConst("018b1fbc-dbcd-724d-a5c7-c1e3778a9674"),
            ],
            [
                'productName' => 'Triangle TR258-16-245-70-111S',
                'productId' => new ProductUid("018b1fbc-dc5a-7328-a30c-683e7e6d172a"),
                'offerConst' => new ProductOfferConst("018b1fbc-dbae-75ac-80cb-2b1626d6a9ce"),
                'variationConst' => new ProductVariationConst("018b1fbc-dbb4-7593-b270-5c9097d1e5ca"),
                'modificationConst' => new ProductModificationConst("018b1fbc-dbb6-74c6-8848-0d2f2891e0c0"),
            ],
        ];

    }

    public function testFindTotal(): void
    {
        /** @var ProductTotalInOrdersInterface $ProductTotalInOrdersInterface */
        $ProductTotalInOrdersInterface = self::getContainer()->get(ProductTotalInOrdersInterface::class);

        foreach(self::$products as $product)
        {
            $result = $ProductTotalInOrdersInterface
                ->onProfile(new UserProfileUid('0196e4d2-5a80-720b-9547-176f35eaae71'))
                ->onProduct($product['productId'])
                ->onOfferConst($product['offerConst'])
                ->onVariationConst($product['variationConst'])
                ->onModificationConst($product['modificationConst'])
                ->findTotal();

            // dump($product['productName'].' всего в заказах: '.$result);
        }

        echo sprintf('%s результат репозитория не протестирован  %s %s', PHP_EOL, self::class, PHP_EOL);
        self::assertTrue(true);
    }
}