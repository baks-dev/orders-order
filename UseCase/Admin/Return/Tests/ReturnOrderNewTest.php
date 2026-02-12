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

namespace BaksDev\Orders\Order\UseCase\Admin\Return\Tests;

use BaksDev\Delivery\Type\Event\DeliveryEventUid;
use BaksDev\Delivery\Type\Field\DeliveryFieldUid;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusReturn;
use BaksDev\Orders\Order\UseCase\Admin\Delete\Tests\DeleteOrderTest;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\Return\Products\Price\ReturnOrderPriceDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\Products\ReturnOrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\ReturnOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\ReturnOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\Return\User\Delivery\Field\ReturnOrderDeliveryFieldDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\User\Delivery\ReturnOrderDeliveryDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\User\Payment\Field\ReturnOrderPaymentFieldDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\User\Payment\ReturnOrderPaymentDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\User\ReturnOrderUserDTO;
use BaksDev\Payment\Type\Field\PaymentFieldUid;
use BaksDev\Payment\Type\Id\Choice\TypePaymentCache;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Reference\Currency\Type\Currencies\RUR;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\UserProfile\Type\Event\UserProfileEventUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('orders-order')]
#[Group('orders-order-usecase')]
#[When(env: 'test')]
final class ReturnOrderNewTest extends KernelTestCase
{
    #[DependsOnClass(DeleteOrderTest::class)]
    public function testUseCase(): void
    {
        // $EditOrderDTO = new EditOrderDTO();
        $ReturnOrderDTO = new ReturnOrderDTO();

        self::assertTrue($ReturnOrderDTO->getStatus()->equals(OrderStatusReturn::class));

        /** OrderProductDTO */

        $ReturnOrderProductDTO = new ReturnOrderProductDTO();
        $ReturnOrderDTO->addProduct($ReturnOrderProductDTO);
        self::assertTrue($ReturnOrderDTO->getProduct()->contains($ReturnOrderProductDTO));

        $ProductEventUid = new ProductEventUid(ProductEventUid::TEST);
        $ReturnOrderProductDTO->setProduct($ProductEventUid);
        self::assertSame($ProductEventUid, $ReturnOrderProductDTO->getProduct());

        $ProductOfferUid = new ProductOfferUid(ProductOfferUid::TEST);
        $ReturnOrderProductDTO->setOffer($ProductOfferUid);
        self::assertSame($ProductOfferUid, $ReturnOrderProductDTO->getOffer());

        $ProductVariationUid = new  ProductVariationUid(ProductVariationUid::TEST);
        $ReturnOrderProductDTO->setVariation($ProductVariationUid);
        self::assertSame($ProductVariationUid, $ReturnOrderProductDTO->getVariation());

        $ProductModificationUid = new ProductModificationUid(ProductModificationUid::TEST);
        $ReturnOrderProductDTO->setModification($ProductModificationUid);
        self::assertSame($ProductModificationUid, $ReturnOrderProductDTO->getModification());

        /** ReturnOrderInvariableDTO */

        $ReturnOrderInvariableDTO = $ReturnOrderDTO->getInvariable(); // читаем из NewOrderDTO

        $ReturnOrderInvariableDTO->setUsr($UserUid = new  UserUid(UserUid::TEST));
        self::assertSame($UserUid, $ReturnOrderInvariableDTO->getUsr());

        $ReturnOrderInvariableDTO->setProfile($UserProfileUid = new UserProfileUid(UserProfileUid::TEST));
        self::assertSame($UserProfileUid, $ReturnOrderInvariableDTO->getProfile());

        /** Взываем метод $ReturnOrderInvariableDTO->getNumber() */
        $number = $ReturnOrderInvariableDTO->getNumber();
        self::assertNotEmpty($number);

        /** OrderPriceDTO */

        $ReturnOrderPriceDTO = new ReturnOrderPriceDTO();
        $ReturnOrderProductDTO->setPrice($ReturnOrderPriceDTO);
        self::assertSame($ReturnOrderPriceDTO, $ReturnOrderProductDTO->getPrice());

        $ReturnOrderPriceDTO->setTotal(200);
        self::assertEquals(200, $ReturnOrderPriceDTO->getTotal());

        $price = new Money(100);
        $ReturnOrderPriceDTO->setPrice($price);
        self::assertSame($price, $ReturnOrderPriceDTO->getPrice());

        $currency = new Currency(RUR::class);
        $ReturnOrderPriceDTO->setCurrency($currency);
        self::assertSame($currency, $ReturnOrderPriceDTO->getCurrency());


        /** OrderUserDTO */

        $ReturnOrderUserDTO = new ReturnOrderUserDTO();
        $ReturnOrderDTO->setUsr($ReturnOrderUserDTO);
        self::assertSame($ReturnOrderUserDTO, $ReturnOrderDTO->getUsr());

        $user = new UserUid(UserUid::TEST);
        $ReturnOrderUserDTO->setUsr($user);
        self::assertSame($user, $ReturnOrderUserDTO->getUsr());

        $profile = new UserProfileEventUid();
        $ReturnOrderUserDTO->setProfile($profile);
        self::assertSame($profile, $ReturnOrderUserDTO->getProfile());


        /** OrderDeliveryDTO */

        $ReturnOrderDeliveryDTO = new ReturnOrderDeliveryDTO();
        $ReturnOrderUserDTO->setDelivery($ReturnOrderDeliveryDTO);
        self::assertSame($ReturnOrderDeliveryDTO, $ReturnOrderUserDTO->getDelivery());

        $delivery = new DeliveryUid(DeliveryUid::TEST);
        $ReturnOrderDeliveryDTO->setDelivery($delivery);
        self::assertSame($delivery, $ReturnOrderDeliveryDTO->getDelivery());

        $event = new DeliveryEventUid(DeliveryEventUid::TEST);
        $ReturnOrderDeliveryDTO->setEvent($event);
        self::assertSame($event, $ReturnOrderDeliveryDTO->getEvent());


        //        $GpsLatitude = new GpsLatitude(55.7522);
        //        $ReturnOrderDeliveryDTO->setLatitude($GpsLatitude);
        //        self::assertSame($GpsLatitude, $ReturnOrderDeliveryDTO->getLatitude());

        //        $GpsLongitude = new GpsLongitude(37.6156);
        //        $ReturnOrderDeliveryDTO->setLongitude($GpsLongitude);
        //        self::assertSame($GpsLongitude, $ReturnOrderDeliveryDTO->getLongitude());

        /** OrderDeliveryFieldDTO */

        $ReturnOrderDeliveryFieldDTO = new ReturnOrderDeliveryFieldDTO();
        $ReturnOrderDeliveryDTO->addField($ReturnOrderDeliveryFieldDTO);
        self::assertTrue($ReturnOrderDeliveryDTO->getField()->contains($ReturnOrderDeliveryFieldDTO));

        $field = new DeliveryFieldUid();
        $ReturnOrderDeliveryFieldDTO->setField($field);
        self::assertSame($field, $ReturnOrderDeliveryFieldDTO->getField());

        $ReturnOrderDeliveryFieldDTO->setValue(UserProfileUid::TEST);
        self::assertEquals(UserProfileUid::TEST, $ReturnOrderDeliveryFieldDTO->getValue());

        /** OrderPaymentDTO */

        $ReturnOrderPaymentDTO = new ReturnOrderPaymentDTO();
        $ReturnOrderUserDTO->setPayment($ReturnOrderPaymentDTO);

        $payment = new PaymentUid(TypePaymentCache::TYPE);
        $ReturnOrderPaymentDTO->setPayment($payment);
        self::assertSame($payment, $ReturnOrderPaymentDTO->getPayment());

        /** OrderPaymentFieldDTO */

        $ReturnOrderPaymentFieldDTO = new ReturnOrderPaymentFieldDTO();
        $ReturnOrderPaymentDTO->addField($ReturnOrderPaymentFieldDTO);
        self::assertTrue($ReturnOrderPaymentDTO->getField()->contains($ReturnOrderPaymentFieldDTO));

        $field = new PaymentFieldUid();
        $ReturnOrderPaymentFieldDTO->setField($field);
        self::assertSame($field, $ReturnOrderPaymentFieldDTO->getField());

        $ReturnOrderPaymentFieldDTO->setValue('XWLoRsQwyq');
        self::assertEquals('XWLoRsQwyq', $ReturnOrderPaymentFieldDTO->getValue());

        self::bootKernel();

        /** @var NewOrderHandler $OrderHandler */
        $ReturnOrderHandler = self::getContainer()->get(ReturnOrderHandler::class);
        $handle = $ReturnOrderHandler->handle($ReturnOrderDTO);
        self::assertTrue(($handle instanceof Order), $handle.': Ошибка Order');
    }

    public static function tearDownAfterClass(): void
    {
        DeleteOrderTest::tearDownAfterClass();
    }
}
