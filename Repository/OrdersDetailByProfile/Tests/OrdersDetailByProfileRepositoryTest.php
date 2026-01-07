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

namespace BaksDev\Orders\Order\Repository\OrdersDetailByProfile\Tests;

use BaksDev\Orders\Order\Repository\OrdersDetailByProfile\OrdersDetailByProfileInterface;
use BaksDev\Orders\Order\Repository\OrdersDetailByProfile\OrdersDetailByProfileResult;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\AdminUserProfile\AdminUserProfileInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('orders-order')]
#[Group('orders-order-repository')]
#[When(env: 'test')]
class OrdersDetailByProfileRepositoryTest extends KernelTestCase
{
    private static array|false $result = false;

    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $profiles = $em
            ->getRepository(UserProfile::class)
            ->findAll();

        self::assertNotEmpty($profiles, 'Не найдено ни одного профиля');

        /** @var OrdersDetailByProfileInterface $repository */
        $repository = self::getContainer()->get(OrdersDetailByProfileInterface::class);

        /**
         * Получаем идентификатор администратора
         * @var AdminUserProfileInterface $AdminUserProfile
         */
        $AdminUserProfile = self::getContainer()->get(AdminUserProfileInterface::class);
        $UserProfileUid = $AdminUserProfile->fetchUserProfile();

        if(false === $UserProfileUid)
        {
            self::$result = false;
            echo PHP_EOL.'Не найден профиль администратора : '.self::class.PHP_EOL;
            return;
        }

        $result = $repository
            ->forProfile($UserProfileUid)
            ->findAllResults();

        if(false === $result || false === $result->valid())
        {
            self::$result = false;
            echo PHP_EOL.'Не найдено ни одного заказа у профиля администратора : '.self::class.':'.__LINE__.PHP_EOL;
            return;
        }

        self::$result = $result->current();
    }

    public function testFindAll(): void
    {
        if(false === self::$result)
        {
            self::assertFalse(self::$result);
            return;
        }

        // Вызываем все геттеры
        $reflectionClass = new ReflectionClass(OrdersDetailByProfileResult::class);
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach($methods as $method)
        {
            // Методы без аргументов
            if($method->getNumberOfParameters() === 0)
            {
                // Вызываем метод
                $data = $method->invoke(self::$result);
                //                 dump($data);
            }
        }
    }
}
