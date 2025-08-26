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

namespace BaksDev\Orders\Order\Messenger\Sticker\Tests;

use ArrayObject;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Messenger\Sticker\OrderStickerMessage;
use BaksDev\Orders\Order\Messenger\Sticker\OrderStickerMiddleware;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group orders-order
 */
#[Group('orders-order')]
#[When(env: 'test')]
class OrderStickerDispatcherTest extends KernelTestCase
{
    public function testUseCase(): void
    {


        /** @see OrderStickerDispatcherDTO */
        $OrderStickerMessage = new OrderStickerMessage(new OrderUid());

        /** @var MessageDispatchInterface $MessageDispatch */
        $MessageDispatch = self::getContainer()->get(MessageDispatchInterface::class);

        $MessageDispatch->dispatch($OrderStickerMessage);

        if($OrderStickerMessage->getResults() instanceof ArrayObject)
        {
            foreach($OrderStickerMessage->getResults() as $number => $code)
            {
                //dump($number, $code);
            }
        }

        self::assertTrue(true);
    }
}