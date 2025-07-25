<?php
/*
 * Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Admin\Print\Tests;

use BaksDev\Orders\Order\UseCase\Admin\Print\OrderEventPrintDTO;
use BaksDev\Orders\Order\UseCase\Admin\Print\OrderEventPrintHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use Symfony\Component\DependencyInjection\Attribute\When;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Entity\Order;

/**
 * @group orders-order
 * @group orders-order-usecase
 */
#[When(env: 'test')]
final class OrderEventPrintHandlerTest extends KernelTestCase
{
    public function testPrint(): void
    {
        /** @var OrderEventPrintHandler $OrderEventPrintHandler */
        $OrderEventPrintHandler = self::getContainer()->get(OrderEventPrintHandler::class);
        $orderEventPrintDTO = new OrderEventPrintDTO(new OrderEventUid(OrderEventUid::TEST));
        $result = $OrderEventPrintHandler->handle($orderEventPrintDTO);

        self::assertInstanceOf(Order::class, $result);
    }
}