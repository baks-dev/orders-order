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

use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group orders-order
 */
#[When(env: 'test')]
class ProductUserBasketTest extends KernelTestCase
{

    public function testUseCase(): void
    {
        /** @var ProductUserBasketInterface $ProductUserBasket */
        $ProductUserBasket = self::getContainer()->get(ProductUserBasketInterface::class);

        $ProductUserBasket
            ->forEvent('70aab9e3-45e6-7e5c-b5f4-5e0089671da5')
            ->forOffer('4cc89718-7374-75c5-9106-ceff55c9f0f4')
            ->forVariation('25a0f020-e6db-7ddf-bdd2-3cb6edaa5e2b')
            ->forModification('63e93c46-3ee9-736b-95ae-83ac926c3155')
            ->findAll();

        if($ProductUserBasket instanceof ProductUserBasketResult)
        {
            self::assertInstanceOf(ProductUid::class, $ProductUserBasket->getProductId());
            self::assertInstanceOf(ProductEventUid::class, $ProductUserBasket->getProductEvent());

            return;
        }

        self::assertFalse($ProductUserBasket);
    }

}