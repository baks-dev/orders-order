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

namespace BaksDev\Orders\Order\UseCase\Admin\Access\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Delivery\Type\Event\DeliveryEventUid;
use BaksDev\Delivery\Type\Field\DeliveryFieldUid;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Repository\RelevantNewOrderByProduct\RelevantNewOrderByProductInterface;
use BaksDev\Orders\Order\UseCase\Admin\Access\AccessOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Access\Products\AccessOrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\Package\PackageOrderDTO;
use BaksDev\Payment\Type\Field\PaymentFieldUid;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
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
#[When(env: 'test')]
final class AccessOrderNewTest extends KernelTestCase
{
    /**
     * @var array|false|mixed[]
     */
    private static array|false $identifier;

    public static function setUpBeforeClass(): void
    {
        // Бросаем событие консольной комманды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

    }

    public function testUseCase(): void
    {

        /** @var RelevantNewOrderByProductInterface $RelevantNewOrderByProduct */
        $RelevantNewOrderByProduct = self::getContainer()->get(RelevantNewOrderByProductInterface::class);


        //        // usr = 0194c726-644b-7f8a-a656-6617f2d3ea2b
        //        0194c6fe-8f9f-70ba-bc77-4f46795765c3,
        //        0194c6fe-8fa2-7056-86bd-55f13a2a8616,
        //        0194c6fe-8fa2-7056-86bd-55f13b69a421,
        //        null


        $DeliveryUid = '018da7ff-69cd-7746-bdd5-9017417c64b2';
        $ProductEventUid = '0194c6fe-8f9f-70ba-bc77-4f46795765c3';
        $ProductOfferUid = '0194c6fe-8fa2-7056-86bd-55f13a2a8616';
        $ProductVariationUid = '0194c6fe-8fa2-7056-86bd-55f13b69a421';
        $ProductModificationUid = null;

        $OrderEvent = $RelevantNewOrderByProduct
            ->forDelivery($DeliveryUid)
            ->forProductEvent($ProductEventUid)
            ->forOffer($ProductOfferUid)
            ->forVariation($ProductVariationUid)
            ->forModification($ProductModificationUid)
            ->onlyNewStatus()
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            self::assertFalse($OrderEvent);
            return;
        }

        /** @var AccessOrderDTO $AccessOrderDTO */
        $AccessOrderDTO = $OrderEvent->getDto(AccessOrderDTO::class);

        /** @var AccessOrderProductDTO $AccessOrderProductDTO */

        foreach($AccessOrderDTO->getProduct() as $AccessOrderProductDTO)
        {
            /**
             * Проверяем, что продукт в заказе соответствует идентификаторам производства
             */

            if(false === $AccessOrderProductDTO->getProduct()->equals($ProductEventUid))
            {
                continue;
            }

            if($AccessOrderProductDTO->getOffer() instanceof ProductOfferUid && false === $AccessOrderProductDTO->getOffer()->equals($ProductOfferUid))
            {
                continue;
            }

            if(true === is_null($AccessOrderProductDTO->getOffer()) && $AccessOrderProductDTO->getOffer() !== $ProductOfferUid)
            {
                continue;
            }


            if($AccessOrderProductDTO->getVariation() instanceof ProductVariationUid && false === $AccessOrderProductDTO->getVariation()->equals($ProductVariationUid))
            {
                continue;
            }

            if(true === is_null($AccessOrderProductDTO->getVariation()) && $AccessOrderProductDTO->getVariation() !== $ProductVariationUid)
            {
                continue;
            }


            if($AccessOrderProductDTO->getModification() instanceof ProductModificationUid && false === $AccessOrderProductDTO->getModification()->equals($ProductModificationUid))
            {
                continue;
            }


            if(true === is_null($AccessOrderProductDTO->getModification()) && $AccessOrderProductDTO->getModification() !== $ProductModificationUid)
            {
                continue;
            }

            $AccessOrderPriceDTO = $AccessOrderProductDTO->getPrice();

            // Пропускаем, если продукция в заказе уже готова к сборке, но еще не отправлена на упаковку
            if(true === $AccessOrderPriceDTO->isAccess())
            {
                continue;
            }

            $AccessOrderPriceDTO->addAccess();

            if(false === $AccessOrderPriceDTO->isAccess())
            {
                // Обновляем на единицу продукции ACCESS в заказе
                $AccessOrderProductDTO->getId();
            }
        }

        /** Проверяем что вся продукция в заказе готова к сборке */

        $isPackage = true;

        foreach($AccessOrderDTO->getProduct() as $AccessOrderProductDTO)
        {
            $AccessOrderPriceDTO = $AccessOrderProductDTO->getPrice();

            if(false === $AccessOrderPriceDTO->isAccess())
            {
                $isPackage = false;
                break;
            }
        }

        if(true === $isPackage)
        {
            $UserProfileUid = new UserProfileUid();

            /** @var PackageOrderDTO $PackageOrderDTO */
            $PackageOrderDTO = $OrderEvent->getDto(PackageOrderDTO::class);
            $PackageOrderDTO->getInvariable()->setProfile($UserProfileUid);
        }

    }

}
