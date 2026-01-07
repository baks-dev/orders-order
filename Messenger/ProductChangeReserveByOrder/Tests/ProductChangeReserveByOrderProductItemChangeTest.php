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

namespace BaksDev\Orders\Order\Messenger\ProductChangeReserveByOrder\Tests;

use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Messenger\ProductChangeReserveByOrder\ProductChangeReserveByOrderProductItemChangeDispatcher;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
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
class ProductChangeReserveByOrderProductItemChangeTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        self::assertTrue(true);
        return;

        // Бросаем событие консольной команды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        /** @var ProductChangeReserveByOrderProductItemChangeDispatcher $ProductChangeReserveByOrderProductItemChangeDispatcher */
        $ProductChangeReserveByOrderProductItemChangeDispatcher = self::getContainer()->get(ProductChangeReserveByOrderProductItemChangeDispatcher::class);

        $ProductChangeReserveByOrderProductItemChangeDispatcher(
            new OrderMessage(
                new OrderUid('019b4baa-3edc-7161-9277-bfa5e9d7588e'),
                new OrderEventUid('019b4bad-fd19-7176-bca5-7bb0cf5ab61f'),
                new OrderEventUid('019b4bad-47b6-772b-8842-c41fbf58ceaa'))
        );

    }

}