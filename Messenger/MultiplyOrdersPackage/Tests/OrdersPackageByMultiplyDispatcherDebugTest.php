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

namespace BaksDev\Products\Stocks\Messenger\Orders\MultiplyProductStocksPackage\Tests;

use BaksDev\Orders\Order\Messenger\MultiplyOrdersPackage\OrdersPackageByMultiplyDispatcher;
use BaksDev\Orders\Order\Messenger\MultiplyOrdersPackage\OrdersPackageByMultiplyMessage;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Group('products-stocks')]
#[When(env: 'test')]
class OrdersPackageByMultiplyDispatcherDebugTest extends KernelTestCase
{

    public function testUseCase(): void
    {
        /** @var OrdersPackageByMultiplyDispatcher $OrdersPackageByMultiplyDispatcher */
        $OrdersPackageByMultiplyDispatcher = self::getContainer()->get(OrdersPackageByMultiplyDispatcher::class);

        self::assertTrue(true);
        return;

        // Бросаем событие консольной команды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        $message = new OrdersPackageByMultiplyMessage(
            new OrderEventUid('019dd056-fd7c-78f8-8dab-28b944f1477d'),
            new UserUid(''),
            new UserProfileUid(''),
        );

        $OrdersPackageByMultiplyDispatcher($message);
    }
}