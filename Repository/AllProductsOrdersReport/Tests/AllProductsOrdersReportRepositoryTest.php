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

namespace BaksDev\Orders\Order\Repository\AllProductsOrdersReport\Tests;

use BaksDev\Orders\Order\Repository\AllProductsOrdersReport\AllProductsOrdersReportInterface;
use BaksDev\Orders\Order\Repository\AllProductsOrdersReport\AllProductsOrdersReportResult;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Tests\ProductsProductNewAdminUseCaseTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('orders-order')]
#[When(env: 'test')]
final class AllProductsOrdersReportRepositoryTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        //ProductsProductNewAdminUseCaseTest::setUpBeforeClass();
        //new ProductsProductNewAdminUseCaseTest('')->testUseCase();
    }

    //#[DependsOnClass(OrderStatusCompleteTest::class)]
    public function testFind(): void
    {
        self::assertTrue(true);

        /** @var AllProductsOrdersReportInterface $allProductsOrdersReportRepository */
        $allProductsOrdersReportRepository = self::getContainer()->get(AllProductsOrdersReportInterface::class);

        $result = $allProductsOrdersReportRepository
            ->forProfile(new UserProfileUid('019577a9-71a3-714b-a99c-0386833d802f'))
            ->from(new DateTimeImmutable()->modify('-1 year'))
            ->to(new DateTimeImmutable())
            ->findAll();

        if(false === $result || false === $result->valid())
        {
            return;
        }

        foreach($result as $AllProductsOrdersReportResult)
        {
            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(AllProductsOrdersReportResult::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $data = $method->invoke($AllProductsOrdersReportResult);
                    //dump($data);
                }
            }

        }


    }
}