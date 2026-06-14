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

namespace BaksDev\Orders\Order\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Messenger\LockOrder\OrderLockMessage;
use BaksDev\Orders\Order\Messenger\LockOrder\OrderUnlockMessage;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Управляет блокировкой заказа, слушая отдельное сообщение о блокировке
 */
#[AsController]
#[RoleSecurity('ROLE_ORDERS_LOCK')]
final class LockController extends AbstractController
{
    /** Блокировка */
    #[Route('/admin/order/lock/{id}', name: 'admin.lock', methods: ['GET', 'POST'])]
    public function cancel(
        #[MapEntity(id: 'id')] Order $Order,
        #[MapQueryParameter] bool $lock,
        Request $request,
        MessageDispatchInterface $messageDispatch,
    ): Response
    {
        if(false === $lock)
        {
            $message = new OrderLockMessage(
                id: $Order->getId(),
                context: self::class.':'.__LINE__
            );
        }

        if(true === $lock)
        {
            $message = new OrderUnlockMessage(
                id: $Order->getId(),
                context: self::class.':'.__LINE__
            );
        }

        $messageDispatch
            ->dispatch(
                message: $message,
                transport: 'orders-order',
            );

        return $this->redirectToReferer();
    }
}
