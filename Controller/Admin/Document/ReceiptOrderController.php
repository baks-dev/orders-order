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

namespace BaksDev\Orders\Order\Controller\Admin\Document;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\Repository\OrderDetailByNumber\OrderDetailByNumberInterface;
use Generator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS')]
final class ReceiptOrderController extends AbstractController
{
    private Generator|array|null $orders = null;

    /**
     * Приходный кассовый ордер
     */
    #[Route('/admin/order/document/receipt/{id}', name: 'admin.document.receipt', methods: ['GET', 'POST'])]
    public function receipt(
        #[MapEntity] Order $Order,
        OrderDetailInterface $orderDetail,
        OrderDetailByNumberInterface $orderDetailByPartRepository,
        #[MapQueryParameter] string|null $number = null,
    ): Response
    {

        /**
         * Информация о заказе
         */

        /** Если нет номера партии - единый заказ. Отдаем информацию о конкретном заказе по его номеру */
        if(null === $number)
        {
            $OrderInfo = $orderDetail
                ->onOrder($Order->getId())
                ->find();

            if(!$OrderInfo)
            {
                return new Response('404 Page Not Found');
            }

            $this->orders[] = $OrderInfo;
        }

        /** Если есть номер партии - заказ был разделен. Ищем связанные заказы по номеру партии */
        if(null !== $number)
        {
            $OrdersInfo = $orderDetailByPartRepository
                ->onNumber($number)
                ->findAll();

            $this->orders = iterator_to_array($OrdersInfo);
        }

        if(true === empty($this->orders))
        {
            return new Response('404 Page Not Found');
        }

        return $this->render([
            'orders' => $this->orders
        ]);
    }
}
