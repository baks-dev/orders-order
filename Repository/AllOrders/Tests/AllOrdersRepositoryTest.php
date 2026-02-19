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
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Repository\AllOrders\Tests;

use BaksDev\Orders\Order\Repository\AllOrders\AllOrdersInterface;
use BaksDev\Orders\Order\Repository\AllOrders\AllOrdersRepository;
use BaksDev\Orders\Order\Repository\AllOrders\AllOrdersResult;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Tests\OrderNewTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('orders-order')]
#[Group('orders-order-repository')]
#[When(env: 'test')]
class AllOrdersRepositoryTest extends KernelTestCase
{
    #[DependsOnClass(OrderNewTest::class)]
    public function testRepository(): void
    {
        //        $profile = $_SERVER['TEST_PROFILE'] ?? '019577a9-71a3-714b-a99c-0386833d802f';

        /** @var AllOrdersInterface $AllOrdersRepository */
        $AllOrdersRepository = self::getContainer()->get(AllOrdersRepository::class);

        $AllOrdersResults = $AllOrdersRepository
            ->status(new OrderStatus(OrderStatusNew::class))
            ->forProfile(new UserProfileUid(UserProfileUid::TEST))
            ->findPaginator()
            ->getData();

        self::assertNotEmpty($AllOrdersResults, 'Не найдена информация о заказах');

        foreach($AllOrdersResults as $AllOrdersResult)
        {
            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(AllOrdersResult::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $get = $method->invoke($AllOrdersResult);
                    //                    dump($get);
                }
            }
        }
    }

}