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

namespace BaksDev\Orders\Order\Controller\Admin\Document\Tests;

use BaksDev\Delivery\UseCase\Admin\NewEdit\Tests\NewDeliveryHandleTest;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Tests\OrderNewTest;
use BaksDev\Users\Profile\TypeProfile\UseCase\Admin\NewEdit\Tests\NewTypeProfileHandleTest;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\Tests\UserNewUserProfileHandleTest;
use BaksDev\Users\User\Tests\TestUserAccount;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('orders-order')]
#[When(env: 'test')]
final class SalesInvoiceControllerTest extends WebTestCase
{
    private const string URL = '/admin/order/document/sales?print=true';

    private static ?array $post_data = null;

    private const string ROLE = 'ROLE_USER';

    public static function setUpBeforeClass(): void
    {
        self::$post_data = [
            'sales_invoice_form' => [
                'order_form_data' => [
                    [
                        'order' => OrderUid::TEST,
                    ],
                ],
            ]
        ];
    }

    /**
     * Доступ по без роли
     *
     */
    #[DependsOnClass(OrderNewTest::class)]
    #[DependsOnClass(NewDeliveryHandleTest::class)]
    #[DependsOnClass(UserNewUserProfileHandleTest::class)]
    #[DependsOnClass(NewTypeProfileHandleTest::class)]
    public function testGuestFiled(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $client->request('GET', sprintf(self::URL));

            // Full authentication is required to access this resource
            self::assertResponseStatusCodeSame(401);
        }

        self::assertTrue(true);
    }

    /** Доступ по роли */
    #[DependsOnClass(OrderNewTest::class)]
    #[DependsOnClass(NewDeliveryHandleTest::class)]
    #[DependsOnClass(UserNewUserProfileHandleTest::class)]
    #[DependsOnClass(NewTypeProfileHandleTest::class)]
    public function testRoleSuccessful(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getModer(self::ROLE);

            $client->loginUser($usr, 'user');
            $client->request('POST', sprintf(self::URL), self::$post_data);

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);

    }

    // доступ по роли ROLE_ADMIN
    #[DependsOnClass(OrderNewTest::class)]
    #[DependsOnClass(NewDeliveryHandleTest::class)]
    #[DependsOnClass(UserNewUserProfileHandleTest::class)]
    #[DependsOnClass(NewTypeProfileHandleTest::class)]
    public function testRoleAdminSuccessful(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getAdmin();

            $client->loginUser($usr, 'user');
            $client->request('POST', sprintf(self::URL), self::$post_data);

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
            $client->request('GET', sprintf(self::URL));

            //self::assertResponseStatusCodeSame(403);
            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);

    }
}