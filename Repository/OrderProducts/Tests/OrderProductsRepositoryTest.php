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

namespace BaksDev\Orders\Order\Repository\OrderProducts\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\OrderProducts\OrderProductsInterface;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group orders-order
 */
#[When(env: 'test')]
class OrderProductsRepositoryTest extends KernelTestCase
{
    private static ?string $identifier = null;

    public static function setUpBeforeClass(): void
    {
        /** @var DBALQueryBuilder $qb */
        $qb = self::getContainer()->get(DBALQueryBuilder::class);
        $dbal = $qb->createQueryBuilder(self::class);

        $dbal
            ->select('ord.id')
            ->from(Order::class, 'ord')
            ->setMaxResults(1);

        self::$identifier = $dbal->fetchOne();
    }

    public function testUseCase(): void
    {
        if(empty(self::$identifier))
        {
            echo PHP_EOL.'Заказа не найдено'.PHP_EOL;
            self::assertFalse(false);
            return;
        }

        /** @var OrderProductsInterface $OrderProducts */
        $OrderProducts = self::getContainer()->get(OrderProductsInterface::class);

        /** */

        $products = $OrderProducts
            ->order(self::$identifier)
            ->findAllProducts();

        self::assertTrue($products->valid());

        /** */

        $products = $OrderProducts
            ->order(new OrderUid(self::$identifier))
            ->findAllProducts();

        self::assertTrue($products->valid());


        /** */

        $this->expectException(InvalidArgumentException::class);

        $OrderProducts
            ->order('')
            ->findAllProducts();

    }

}