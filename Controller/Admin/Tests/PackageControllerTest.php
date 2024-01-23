<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Orders\Order\Controller\Admin\Tests;

use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\User\Tests\TestUserAccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use BaksDev\Orders\Order\Controller\Admin\Tests\DetailControllerTest;

/**
 * @group orders-order
 *
 * @see     DetailControllerTest
 * @depends BaksDev\Orders\Order\Controller\Admin\Tests\DetailControllerTest::class
 */
#[When(env: 'test')]
final class PackageControllerTest extends WebTestCase
{
    private const URL = '/admin/order/package/%s';

    private const ROLE = 'ROLE_ORDERS_STATUS';


    public static function tearDownAfterClass(): void
    {
        /**
         * Очищаем все события
         * @var EntityManagerInterface $em
         */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $Order = $em->getRepository(Order::class)->find(OrderUid::TEST);
        self::assertNotNull($Order);
        $em->remove($Order);

        $OrderEvent = $em->getRepository(OrderEvent::class)->find(OrderEventUid::TEST);
        self::assertNotNull($OrderEvent);
        $em->remove($OrderEvent);

        $em->flush();
        $em->clear();

        $Order = $em->getRepository(Order::class)->find(OrderUid::TEST);
        self::assertNull($Order);

        $OrderEvent = $em->getRepository(OrderEvent::class)->find(OrderEventUid::TEST);
        self::assertNull($OrderEvent);

        $em->clear();
        //$em->close();
    }

    /**
     * Доступ по без роли
     *
     */
    public function testGuestFiled(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $client->request('GET', sprintf(self::URL, OrderUid::TEST));

            // Full authentication is required to access this resource
            self::assertResponseStatusCodeSame(401);
        }

        self::assertTrue(true);

    }

    /** Доступ по роли */
    public function testRoleSuccessful(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getModer(self::ROLE);

            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, OrderUid::TEST));

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);
    }

    // доступ по роли ROLE_ADMIN
    public function testRoleAdminSuccessful(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getAdmin();

            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, OrderUid::TEST));

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);
    }

    // доступ по роли ROLE_USER
    public function testRoleUserDeny(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getUsr();
            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, OrderUid::TEST));

            self::assertResponseStatusCodeSame(403);
        }

        self::assertTrue(true);
    }

}
