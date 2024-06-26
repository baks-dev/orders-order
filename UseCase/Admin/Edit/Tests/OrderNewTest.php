<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\Tests;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Delivery\Type\Event\DeliveryEventUid;
use BaksDev\Delivery\Type\Field\DeliveryFieldUid;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\Price\OrderPriceDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery\Field\OrderDeliveryFieldDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery\OrderDeliveryDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\OrderUserDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Payment\Field\OrderPaymentFieldDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Payment\OrderPaymentDTO;
use BaksDev\Payment\Type\Field\PaymentFieldUid;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Reference\Currency\Type\Currencies\RUR;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\UserProfile\Type\Event\UserProfileEventUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/** @group orders-order */
#[When(env: 'test')]
final class OrderNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $Order = $em->getRepository(Order::class)
            ->find(OrderUid::TEST);

        if($Order)
        {
            $em->remove($Order);

            $OrderEvent = $em->getRepository(OrderEvent::class)
                ->findBy(['orders' => OrderUid::TEST]);

            foreach($OrderEvent as $remove)
            {
                $em->remove($remove);
            }

            $em->flush();
        }

        $em->clear();
        //$em->close();

    }

    public function testUseCase(): void
    {
        $OrderDTO = new EditOrderDTO();

        $status = new OrderStatus(new OrderStatus\OrderStatusNew());
        $OrderDTO->setStatus($status);
        self::assertSame($status, $OrderDTO->getStatus());

        /** OrderProductDTO */

        $OrderProductDTO = new OrderProductDTO();
        $OrderDTO->addProduct($OrderProductDTO);
        self::assertTrue($OrderDTO->getProduct()->contains($OrderProductDTO));

        $product = new ProductEventUid();
        $OrderProductDTO->setProduct($product);
        self::assertSame($product, $OrderProductDTO->getProduct());

        $offer = new ProductOfferUid();
        $OrderProductDTO->setOffer($offer);
        self::assertSame($offer, $OrderProductDTO->getOffer());

        $variation = new  ProductVariationUid();
        $OrderProductDTO->setVariation($variation);
        self::assertSame($variation, $OrderProductDTO->getVariation());

        $modification = new ProductModificationUid();
        $OrderProductDTO->setModification($modification);
        self::assertSame($modification, $OrderProductDTO->getModification());


        /** OrderPriceDTO */

        $OrderPriceDTO = new OrderPriceDTO();
        $OrderProductDTO->setPrice($OrderPriceDTO);
        self::assertSame($OrderPriceDTO, $OrderProductDTO->getPrice());

        $OrderPriceDTO->setTotal(200);
        self::assertEquals(200, $OrderPriceDTO->getTotal());

        $price = new Money(100);
        $OrderPriceDTO->setPrice($price);
        self::assertSame($price, $OrderPriceDTO->getPrice());

        $currency = new Currency(RUR::class);
        $OrderPriceDTO->setCurrency($currency);
        self::assertSame($currency, $OrderPriceDTO->getCurrency());


        /** OrderUserDTO */

        $OrderUserDTO = new OrderUserDTO();
        $OrderDTO->setUsr($OrderUserDTO);
        self::assertSame($OrderUserDTO, $OrderDTO->getUsr());

        $user = new UserUid();
        $OrderUserDTO->setUsr($user);
        self::assertSame($user, $OrderUserDTO->getUsr());

        $profile = new UserProfileEventUid();
        $OrderUserDTO->setProfile($profile);
        self::assertSame($profile, $OrderUserDTO->getProfile());


        /** OrderDeliveryDTO */

        $OrderDeliveryDTO = new OrderDeliveryDTO();
        $OrderUserDTO->setDelivery($OrderDeliveryDTO);
        self::assertSame($OrderDeliveryDTO, $OrderUserDTO->getDelivery());

        $delivery = new DeliveryUid();
        $OrderDeliveryDTO->setDelivery($delivery);
        self::assertSame($delivery, $OrderDeliveryDTO->getDelivery());

        $event = new DeliveryEventUid();
        $OrderDeliveryDTO->setEvent($event);
        self::assertSame($event, $OrderDeliveryDTO->getEvent());


        $GpsLatitude = new GpsLatitude(55.7522);
        $OrderDeliveryDTO->setLatitude($GpsLatitude);
        self::assertSame($GpsLatitude, $OrderDeliveryDTO->getLatitude());

        $GpsLongitude = new GpsLongitude(37.6156);
        $OrderDeliveryDTO->setLongitude($GpsLongitude);
        self::assertSame($GpsLongitude, $OrderDeliveryDTO->getLongitude());


        /** OrderDeliveryFieldDTO */

        $OrderDeliveryFieldDTO = new OrderDeliveryFieldDTO();
        $OrderDeliveryDTO->addField($OrderDeliveryFieldDTO);
        self::assertTrue($OrderDeliveryDTO->getField()->contains($OrderDeliveryFieldDTO));

        $field = new DeliveryFieldUid();
        $OrderDeliveryFieldDTO->setField($field);
        self::assertSame($field, $OrderDeliveryFieldDTO->getField());


        $OrderDeliveryFieldDTO->setValue('mQBSkMEHTW');
        self::assertEquals('mQBSkMEHTW', $OrderDeliveryFieldDTO->getValue());


        /** OrderPaymentDTO */

        $OrderPaymentDTO = new OrderPaymentDTO();
        $OrderUserDTO->setPayment($OrderPaymentDTO);

        $payment = new PaymentUid();
        $OrderPaymentDTO->setPayment($payment);
        self::assertSame($payment, $OrderPaymentDTO->getPayment());


        /** OrderPaymentFieldDTO */

        $OrderPaymentFieldDTO = new OrderPaymentFieldDTO();
        $OrderPaymentDTO->addField($OrderPaymentFieldDTO);
        self::assertTrue($OrderPaymentDTO->getField()->contains($OrderPaymentFieldDTO));

        $field = new PaymentFieldUid();
        $OrderPaymentFieldDTO->setField($field);
        self::assertSame($field, $OrderPaymentFieldDTO->getField());

        $OrderPaymentFieldDTO->setValue('XWLoRsQwyq');
        self::assertEquals('XWLoRsQwyq', $OrderPaymentFieldDTO->getValue());


        self::bootKernel();

        /** @var EditOrderHandler $OrderHandler */
        $OrderHandler = self::getContainer()->get(EditOrderHandler::class);
        $handle = $OrderHandler->handle($OrderDTO);
        self::assertTrue(($handle instanceof Order), $handle.': Ошибка Order');


    }

    public function testComplete(): void
    {
        self::assertTrue(true);
    }
}
