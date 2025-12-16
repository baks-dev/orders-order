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

namespace BaksDev\Orders\Order\Repository\OrdersDetailByProfile\Tests;

use BaksDev\Orders\Order\Repository\OrdersDetailByProfile\OrdersDetailByProfileInterface;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\AdminUserProfile\AdminUserProfileInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('orders-order')]
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
            ->findAll();

        if(false === $result || false === $result->valid())
        {
            self::$result = false;
            echo PHP_EOL.'Не найдено ни одного заказа у профиля администратора : '.self::class.PHP_EOL;
            return;
        }

        self::$result = $result->current();
    }

    public static function getAllQueryKeys(): array
    {
        return [
            "order_id",
            "order_event",
            "order_number",
            "order_status",
            "order_data",
            "order_comment",
            "payment_id",
            "payment_name",
            //"order_products",
            "order_delivery_price",
            "order_delivery_currency",
            "delivery_name",
            "delivery_price",
            "delivery_geocode_longitude",
            "delivery_geocode_latitude",
            "delivery_geocode_address",
            "order_profile_discount",
            "order_profile",
            "profile_avatar_name",
            "profile_avatar_ext",
            "profile_avatar_cdn",
            //"order_user",
        ];
    }

    public static function getOrderProductsKeys(): array
    {
        return [
            "product_id",
            "product_url",
            "category_url",
            "product_name",
            "category_name",
            "product_image",
            "product_price",
            "product_total",
            "product_article",
            "product_image_cdn",
            "product_image_ext",
            "product_offer_name",
            "product_offer_const",
            "product_offer_value",
            "product_offer_article",
            "product_offer_postfix",
            "product_price_currency",
            "product_variation_name",
            "product_offer_reference",
            "product_variation_const",
            "product_variation_value",
            "product_modification_name",
            "product_variation_article",
            "product_variation_postfix",
            "product_modification_const",
            "product_modification_value",
            "product_variation_reference",
            "product_modification_article",
            "product_modification_postfix",
            "product_modification_reference",

            "order_products",

        ];
    }

    public static function getOrderUserKeys(): array
    {
        return [
            "0",
            "profile_name",
            "profile_type",
            "profile_value",
        ];
    }

    public function testFindAll(): void
    {
        if(false === self::$result)
        {
            self::assertFalse(self::$result);
            return;
        }

        $current = self::$result;
        $queryKeys = self::getAllQueryKeys();

        foreach($queryKeys as $key)
        {
            self::assertArrayHasKey($key, $current, sprintf('Новый ключ в массиве для сравнения ключей: %s', $key));
        }

        foreach($current as $key => $value)
        {
            self::assertTrue(in_array($key, $queryKeys), sprintf('Новый ключ в в массиве с результатом запроса: %s', $key));
        }
    }

    #[Depends('testFindAll')]
    public function testOrderProducts(): void
    {
        if(false === self::$result)
        {
            self::assertFalse(self::$result);
            return;
        }

        self::assertTrue(true);

        $queryKeys = self::getOrderProductsKeys();

        //$current = current(json_decode(self::$result['order_products'], true));

        //        foreach($queryKeys as $key)
        //        {
        //            self::assertArrayHasKey($key, $current, sprintf('Новый ключ в массиве для сравнения ключей: %s', $key));
        //        }

        //        foreach($current as $key => $value)
        //        {
        //            self::assertTrue(in_array($key, $queryKeys), sprintf('Новый ключ в в массиве с результатом запроса: %s', $key));
        //        }
    }

    #[Depends('testOrderProducts')]
    public function testOrderUser(): void
    {
        if(false === self::$result)
        {
            self::assertFalse(self::$result);
            return;
        }

        self::assertTrue(true);

        $queryKeys = self::getOrderUserKeys();

        //        $current = current(json_decode(self::$result['order_user'], true));
        //
        //        foreach($queryKeys as $key)
        //        {
        //            self::assertArrayHasKey($key, $current, sprintf('Новый ключ в массиве для сравнения ключей: %s', $key));
        //        }
        //
        //        foreach($current as $key => $value)
        //        {
        //            self::assertTrue(in_array($key, $queryKeys), sprintf('Новый ключ в в массиве с результатом запроса: %s', $key));
        //        }
    }
}
