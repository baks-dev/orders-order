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

namespace BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailByNumber\Tests;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailByNumber\OrderDetailByNumberInterface;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailResult;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Tests\OrderNewTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\Query\Expr\Join;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('orders-order')]
#[Group('orders-order-repository')]
#[When(env: 'test')]
class OrderDetailByNumberRepositoryTest extends KernelTestCase
{
    #[DependsOnClass(OrderNewTest::class)]
    public function testRepository(): void
    {
        /** @var OrderDetailByNumberInterface $OrderDetailByPartInterface */
        $OrderDetailByPartInterface = self::getContainer()->get(OrderDetailByNumberInterface::class);

        /**
         * @note Ищем номер тестового заказа
         */

        /** @var ORMQueryBuilder $dbal */
        $dbal = self::getContainer()->get(ORMQueryBuilder::class);

        $qb = $dbal->createQueryBuilder(self::class);

        $qb
            ->select('orders_invariable')
            ->from(OrderInvariable::class, 'orders_invariable');

        $qb->join(
            Order::class,
            'main',
            Join::WITH,
            'main.id = orders_invariable.main AND main.id = :main'
        )
            ->setParameter(
                key: 'main',
                value: OrderUid::TEST,
                type: OrderUid::TYPE
            );

        $OrderInvariable = $qb->getOneOrNullResult();

        self::assertTrue(($OrderInvariable instanceof OrderInvariable), 'Не найден номер тестового заказа');

        $results = $OrderDetailByPartInterface
            ->onOrderNumber($OrderInvariable->getNumber())
            ->forProfile(new UserProfileUid(UserProfileUid::TEST))
            ->findAll();

        self::assertNotFalse($results, 'Не найдена информация о заказах');

        /** @var OrderDetailResult $result */
        $result = $results->current();

        $result->setQrCode('oDCBA5juFxP');

        // Вызываем все геттеры
        $reflectionClass = new ReflectionClass(OrderDetailResult::class);
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach($methods as $method)
        {
            // Методы без аргументов
            if($method->getNumberOfParameters() === 0)
            {
                // Вызываем метод
                $data = $method->invoke($result);
                //                dump($data);
            }
        }
    }
}