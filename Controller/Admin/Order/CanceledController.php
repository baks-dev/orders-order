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

namespace BaksDev\Orders\Order\Controller\Admin\Order;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Forms\Canceled\CanceledOrdersDTO;
use BaksDev\Orders\Order\Forms\Canceled\CanceledOrdersForm;
use BaksDev\Orders\Order\Forms\Canceled\Orders\CanceledOrdersOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Canceled\CanceledOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_STATUS')]
final class CanceledController extends AbstractController
{
    /** Отмена заказа с указанием причины */
    #[Route('/admin/order/canceled', name: 'admin.order.canceled', methods: ['GET', 'POST'])]
    public function canceled(
        Request $request,
        EntityManagerInterface $EntityManager,
        CentrifugoPublishInterface $publish,
        OrderStatusHandler $OrderStatusHandler,
    ): Response
    {
        $canceledOrdersDTO = new CanceledOrdersDTO();
        $canceledOrdersForm = $this->createForm(
            CanceledOrdersForm::class,
            $canceledOrdersDTO,
            ['action' => $this->generateUrl('orders-order:admin.order.canceled')],
        )
            ->handleRequest($request);

        if(
            $canceledOrdersForm->isSubmitted()
            && $canceledOrdersForm->isValid()
            && $canceledOrdersForm->has('order_cancel')
        )
        {

            $this->refreshTokenForm($canceledOrdersForm);

            $unsuccessful = [];
            foreach($canceledOrdersDTO->getOrders() as $order)
            {
                /** Пробуем найти по идентификатору заказа */
                $orderMain = $EntityManager->getRepository(Order::class)->find($order->getId());
                if(false === ($orderMain instanceof Order))
                {
                    continue;
                }

                $orderEvent = $EntityManager->getRepository(OrderEvent::class)->find($orderMain->getEvent());

                if(false === ($orderEvent instanceof OrderEvent))
                {
                    continue;
                }

                $orderCanceledDTO = new CanceledOrderDTO();
                $orderEvent->getDto($orderCanceledDTO);

                /** Присваиваем комментарий из формы только в случае, если не было комментария у заказа */
                if(empty($orderCanceledDTO->getComment()))
                {
                    $orderCanceledDTO->setComment($canceledOrdersDTO->getComment());
                }

                $handle = $OrderStatusHandler->handle($orderCanceledDTO);

                if(false === ($handle instanceof Order))
                {
                    $unsuccessful[] = $orderEvent->getOrderNumber();
                }
            }

            if(true === empty($unsuccessful))
            {
                return new JsonResponse(
                    [
                        'type' => 'success',
                        'header' => 'Отмена заказов',
                        'message' => 'Статусы заказов '.implode(',', $unsuccessful).' успешно обновлены',
                        'status' => 200,
                    ],
                    200,
                );
            }

            $this->addFlash(
                'page.cancel',
                'danger.cancel',
                'orders-order.admin',
                $unsuccessful,
            );

            return $this->redirectToReferer();  // отмена заказа может происходить в других разделах
        }

        $numbers = [];
        /**
         * Если не было сабмита формы - рендерим её
         *
         * @var CanceledOrdersOrderDTO $order
         */
        foreach($canceledOrdersDTO->getOrders() as $order)
        {
            /** Пробуем найти по идентификатору заказа */
            $orderMain = $EntityManager->getRepository(Order::class)->find($order->getId());
            if(false === ($orderMain instanceof Order))
            {
                continue;
            }

            $orderEvent = $EntityManager->getRepository(OrderEvent::class)->find($orderMain->getEvent());

            if(false === ($orderEvent instanceof OrderEvent))
            {
                continue;
            }

            /**
             * Отправляем сокет для скрытия заказа у других менеджеров
             */
            $publish
                ->addData(['order' => (string) $orderEvent->getMain()])
                ->addData(['profile' => (string) $this->getCurrentProfileUid()])
                ->send('orders');

            $numbers[] = $orderEvent->getOrderNumber();
        }

        return $this->render([
            'form' => $canceledOrdersForm->createView(),
            'numbers' => $numbers,
        ]);
    }
}