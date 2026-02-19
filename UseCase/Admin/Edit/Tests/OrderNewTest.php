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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\Tests;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Delivery\Type\Event\DeliveryEventUid;
use BaksDev\Delivery\Type\Field\DeliveryFieldUid;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\New\Products\Items\OrderProductItemDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\Products\NewOrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\Products\Price\NewOrderPriceDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\User\Delivery\Field\OrderDeliveryFieldDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\User\Delivery\OrderDeliveryDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\User\OrderUserDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\User\Payment\Field\OrderPaymentFieldDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\User\Payment\OrderPaymentDTO;
use BaksDev\Payment\Type\Field\PaymentFieldUid;
use BaksDev\Payment\Type\Id\Choice\TypePaymentCache;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Tests\ProductsProductNewAdminUseCaseTest;
use BaksDev\Reference\Currency\Type\Currencies\RUR;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\UserProfile\Type\Event\UserProfileEventUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Group('orders-order')]
#[Group('orders-order-repository')]
#[Group('orders-order-usecase')]
#[When(env: 'test')]
final class OrderNewTest extends KernelTestCase
{

    public static function setUpBeforeClass(): void
    {
        $container = self::getContainer();

        // Бросаем событие консольной комманды
        $dispatcher = $container->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $Order = $em->getRepository(Order::class)
            ->find(OrderUid::TEST);

        if($Order)
        {
            $em->remove($Order);
        }

        $OrderEvent = $em->getRepository(OrderEvent::class)
            ->findBy(['orders' => OrderUid::TEST]);

        foreach($OrderEvent as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();

        /** Создаем тестовый продукт */
        ProductsProductNewAdminUseCaseTest::setUpBeforeClass();
        new ProductsProductNewAdminUseCaseTest('')->testUseCase();

    }

    public function testUseCase(): void
    {
        /**
         * зависимость на создание продукта - модуль products-product
         */

        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $productEvent = $em->getRepository(ProductEvent::class)
            ->find(ProductEventUid::TEST);


        self::assertTrue(($productEvent instanceof ProductEvent), 'Не создан продукт для тестирования заказа');


        // $EditOrderDTO = new EditOrderDTO();
        $NewOrderDTO = new NewOrderDTO();

        self::assertTrue($NewOrderDTO->getStatus()->equals(OrderStatusNew::class));

        $NewOrderDTO->getInvariable()->setProfile($UserProfileUid = new  UserProfileUid());
        self::assertSame($UserProfileUid, $NewOrderDTO->getInvariable()->getProfile());

        /**
         * OrderProductDTO
         */

        /** Идентификаторы из тестового продукта */

        /** @var ProductOffer $ProductOffer */
        $ProductOffer = $productEvent->getOffer()->current();

        /** @var ProductVariation $ProductVariation */
        $ProductVariation = $ProductOffer->getVariation()->current();

        /** @var ProductModification $ProductModification */
        $ProductModification = $ProductVariation->getModification()->current();

        $OrderProductDTO = new NewOrderProductDTO();

        $NewOrderDTO->addProduct($OrderProductDTO);
        self::assertTrue($NewOrderDTO->getProduct()->contains($OrderProductDTO));

        $product = $productEvent->getId();
        $OrderProductDTO->setProduct($product);
        self::assertSame($product, $OrderProductDTO->getProduct());

        $offer = $ProductOffer->getId();
        $OrderProductDTO->setOffer($offer);
        self::assertSame($offer, $OrderProductDTO->getOffer());

        $variation = $ProductVariation->getId();
        $OrderProductDTO->setVariation($variation);
        self::assertSame($variation, $OrderProductDTO->getVariation());

        $modification = $ProductModification->getId();
        $OrderProductDTO->setModification($modification);
        self::assertSame($modification, $OrderProductDTO->getModification());

        /** NewOrderInvariableDTO */

        $NewOrderInvariableDTO = $NewOrderDTO->getInvariable(); // читаем из NewOrderDTO

        $NewOrderInvariableDTO->setUsr($UserUid = new  UserUid());
        self::assertSame($UserUid, $NewOrderInvariableDTO->getUsr());

        $NewOrderInvariableDTO->setProfile($UserProfileUid = new  UserProfileUid());
        self::assertSame($UserProfileUid, $NewOrderInvariableDTO->getProfile());

        /** Взываем метод $NewOrderInvariableDTO->getNumber() */
        $number = $NewOrderInvariableDTO->getNumber();
        self::assertNotEmpty($number);


        /** NewOrderPostingDTO */

        $NewOrderInvariableDTO = $NewOrderDTO->getPosting(); // читаем из NewOrderDTO
        $NewOrderInvariableDTO->setValue($NewOrderDTO->getInvariable()->getNumber());

        /** OrderPriceDTO */

        $OrderPriceDTO = new NewOrderPriceDTO();
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


        /**
         * OrderProductItemDTO
         * Создаем единицу продукта по количеству продукта в заказе
         */
        foreach($NewOrderDTO->getProduct() as $product)
        {
            for($i = 0; $i < $product->getPrice()->getTotal(); $i++)
            {
                $item = new OrderProductItemDTO;

                /**
                 * Присваиваем цену из продукта в заказе
                 */
                $item->getPrice()
                    ->setPrice($product->getPrice()->getPrice())
                    ->setCurrency($product->getPrice()->getCurrency());

                $product->addItem($item);
            }
        }

        /** OrderUserDTO */

        $OrderUserDTO = new OrderUserDTO();
        $NewOrderDTO->setUsr($OrderUserDTO);
        self::assertSame($OrderUserDTO, $NewOrderDTO->getUsr());

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

        $OrderDeliveryFieldDTO->setValue(UserProfileUid::TEST);
        self::assertEquals(UserProfileUid::TEST, $OrderDeliveryFieldDTO->getValue());

        /** OrderPaymentDTO */

        $OrderPaymentDTO = new OrderPaymentDTO();
        $OrderUserDTO->setPayment($OrderPaymentDTO);

        $payment = new PaymentUid(TypePaymentCache::TYPE);
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

        /** @var NewOrderHandler $OrderHandler */
        $OrderHandler = self::getContainer()->get(NewOrderHandler::class);
        $handle = $OrderHandler->handle($NewOrderDTO, false);

        self::assertTrue(($handle instanceof Order), $handle.': Ошибка Order');
    }
}
