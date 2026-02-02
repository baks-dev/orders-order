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

namespace BaksDev\Orders\Order\UseCase\Admin\Delete\Tests;

use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\UseCase\Admin\Delete\DeleteOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Delete\DeleteOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Tests\OrderNewTest;
use BaksDev\Orders\Order\UseCase\Admin\Status\Tests\OrderStatusCompleteTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('orders-order')]
class DeleteOrderTest extends KernelTestCase
{
    #[DependsOnClass(OrderNewTest::class)]
    #[DependsOnClass(OrderStatusCompleteTest::class)]
    public function testUseCase(): void
    {
        /** @var CurrentOrderEventInterface $OrderCurrentEvent */
        $OrderCurrentEvent = self::getContainer()->get(CurrentOrderEventInterface::class);
        $OrderEvent = $OrderCurrentEvent->forOrder(OrderUid::TEST)->find();
        self::assertNotNull($OrderEvent);

        /** @see OrderDeleteDTO */
        $OrderDeleteDTO = new DeleteOrderDTO();
        $OrderEvent->getDto($OrderDeleteDTO);

        /** @var DeleteOrderHandler $OrderHandler */
        $OrderDeleteHandler = self::getContainer()->get(DeleteOrderHandler::class);
        $handle = $OrderDeleteHandler->handle($OrderDeleteDTO);

        self::assertTrue(($handle instanceof Order), $handle.': Ошибка Order');

    }

    public static function tearDownAfterClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $main = $em->getRepository(Order::class)
            ->findOneBy(['id' => OrderUid::TEST]);

        if($main)
        {
            $em->remove($main);
        }


        $event = $em->getRepository(OrderEvent::class)
            ->findBy(['orders' => OrderUid::TEST]);

        foreach($event as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
        $em->clear();
    }
}
