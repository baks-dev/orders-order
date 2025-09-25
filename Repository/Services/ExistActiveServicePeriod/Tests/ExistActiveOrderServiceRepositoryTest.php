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
 *
 */

namespace BaksDev\Orders\Order\Repository\Services\ExistActiveServicePeriod\Tests;

use BaksDev\Orders\Order\Repository\Services\ExistActiveServicePeriod\ExistActiveOrderServiceInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\OrderService\Period\ServicePeriodUid;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('orders-order')]
#[Group('orders-order-repo')]
#[When(env: 'test')]
class ExistActiveOrderServiceRepositoryTest extends KernelTestCase
{
    public function testRepository(): void
    {
        self::assertTrue(true);

        /** @var ExistActiveOrderServiceInterface $ExistActiveOrderServiceInterface */
        $ExistActiveOrderServiceInterface = self::getContainer()->get(ExistActiveOrderServiceInterface::class);

        $result = $ExistActiveOrderServiceInterface
            ->byEvent(new OrderEventUid())
            ->byPeriod(new ServicePeriodUid())
            ->byDate(new \DateTimeImmutable())
            ->exist();
    }
}