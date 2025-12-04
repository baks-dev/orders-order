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

namespace BaksDev\Orders\Order\Repository\AllServicesOrdersReport\Tests;

use BaksDev\Orders\Order\Repository\AllServicesOrdersReport\AllServicesOrdersReportInterface;
use BaksDev\Orders\Order\Repository\AllServicesOrdersReport\AllServicesOrdersReportRepository;
use BaksDev\Orders\Order\Repository\AllServicesOrdersReport\AllServicesOrdersReportResult;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('orders-order')]
#[When(env: 'test')]
final class AllServicesOrdersReportRepositoryTest extends KernelTestCase
{

    public function testRepo(): void
    {
        /** @var AllServicesOrdersReportRepository $AllServicesOrdersReportRepository */
        $AllServicesOrdersReportRepository = self::getContainer()->get(AllServicesOrdersReportInterface::class);

        /* Получить заказы за месяц */
        $fiveMonthsAgo = new DateTimeImmutable('last month');

        /** @var \Generator $result */
        $result = $AllServicesOrdersReportRepository
            ->from($fiveMonthsAgo)
            ->to(new DateTimeImmutable())
            //            ->forProfile(new UserProfileUid())
            ->findAll();

        if(false === $result)
        {
            self::assertFalse(false);
            return;
        }

        /** @var AllServicesOrdersReportResult $AllServicesOrdersReportResult */
        foreach($result as $AllServicesOrdersReportResult)
        {

            /* Вызывать все геттеры */
            $reflectionClass = new ReflectionClass(AllServicesOrdersReportResult::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                /* Методы без аргументов */
                if($method->getNumberOfParameters() === 0)
                {
                    /* Вызвать метод */
                    $data = $method->invoke($AllServicesOrdersReportResult);
//                    dump($data);
                }
            }

        }

        self::assertTrue(true);
    }
}