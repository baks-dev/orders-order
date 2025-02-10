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

namespace BaksDev\Orders\Order\Repository\RelevantNewOrderByProduct\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Repository\RelevantNewOrderByProduct\RelevantNewOrderByProductInterface;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group orders-order
 */
#[When(env: 'test')]
class RelevantNewOrderByProductRepositoryTest extends KernelTestCase
{

    public function testUseCase(): void
    {
        self::assertTrue(true);
        return;

        /** @var RelevantNewOrderByProductInterface $RelevantNewOrderByProduct */
        $RelevantNewOrderByProduct = self::getContainer()->get(RelevantNewOrderByProductInterface::class);

        $OrderEvent = $RelevantNewOrderByProduct
            ->forDelivery(TypeDeliveryFbsWildberries::TYPE)
            ->forProductEvent('0194bd11-f17f-7c5b-a32d-5c000c0980d1')
            ->forOffer('0194bd11-f180-7b05-84d1-da3de1d8e987')
            ->forVariation('0194bd11-f182-7cbe-b5bf-c7c49d905321')
            ->forModification(null)
            ->onlyPackageStatus()
            ->find();

        dd($OrderEvent); /* TODO: удалить !!! */

    }
}