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

namespace BaksDev\Orders\Order\Controller\User;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Repository\OrdersDetailByProfile\OrdersDetailByProfileInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('/orders/{status}/{page<\d+>}', name: 'user.orders')]
    public function index(
        OrdersDetailByProfileInterface $ordersDetailByProfileRepository,
        string $status,
        int $page = 0,
    ): Response
    {
        $profile = $this->getCurrentProfileUid();

        if(is_null($profile))
        {
            return $this->redirectToReferer();
        }

        /**
         * Проверка соответствия переданного статуса системным статусам. Если передан несуществующий статус ->
         * @throws InvalidArgumentException
         */
        foreach(OrderStatus::cases() as $case)
        {
            if($case->equals($status))
            {
                /** @var OrderStatus $status */
                $status = $case;
            }
        }

        $ordersPaginator = $ordersDetailByProfileRepository
            ->byStatus($status)
            ->byProfile($profile)
            ->findAllWithPaginator();

        return $this->render([
            'orders' => $ordersPaginator,
            'status' => $status,
        ]);
    }
}
