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

namespace BaksDev\Orders\Order\Controller\Admin;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\Repository\OrderHistory\OrderHistoryInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCollection;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

#[AsController]
#[RoleSecurity('ROLE_ORDERS')]
final class DetailController extends AbstractController
{
    #[Route('/admin/order/detail/{id}', name: 'admin.detail', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        #[MapEntity] Order $Order,
        CurrentOrderEventInterface $currentOrderEvent,
        ProductUserBasketInterface $userBasket,
        OrderDetailInterface $orderDetail,
        OrderHistoryInterface $orderHistory,
        OrderStatusCollection $collection,
        EditOrderHandler $handler,
        CentrifugoPublishInterface $publish,
        string $id,
    ): Response
    {
        /** Получаем активное событие заказа */
        $OrderEvent = $currentOrderEvent
            ->forOrder($Order->getId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            throw new RouteNotFoundException('Page Not Found');
        }

        /** Информация о заказе */
        $OrderInfo = $orderDetail->fetchDetailOrderAssociative($OrderEvent->getMain());

        if(!$OrderInfo)
        {
            return new Response('404 Page Not Found');
        }

        $OrderDTO = new EditOrderDTO($Order->getId());
        $OrderEvent->getDto($OrderDTO);

        /** @var OrderProductDTO $product */
        foreach($OrderDTO->getProduct() as $product)
        {
            $ProductDetail = $userBasket->fetchProductBasketAssociative(
                $product->getProduct(),
                $product->getOffer(),
                $product->getVariation(),
                $product->getModification()
            );

            $product->setCard($ProductDetail);
        }

        // форма заказа
        $form = $this
            ->createForm(
                type: EditOrderForm::class,
                data: $OrderDTO,
                options: ['action' => $this->generateUrl('orders-order:admin.detail', ['id' => $id])]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && false === $form->isValid())
        {
            return $this->redirectToReferer();
        }

        if($form->isSubmitted() && $form->isValid())
        {
            $this->refreshTokenForm($form);

            $OrderHandler = $handler->handle($OrderDTO);

            if($OrderHandler instanceof Order)
            {
                $this->addFlash('success', 'success.update', 'orders-order.admin');
            }
            else
            {
                $this->addFlash('danger', 'danger.update', 'orders-order.admin', $OrderHandler);
            }

            return $this->redirectToReferer();
        }


        /** История изменения статусов */
        $History = $orderHistory
            ->order($OrderEvent->getMain())
            ->findAllHistory();

        // Отпарвляем сокет для скрытия заказа у других менеджеров
        $socket = $publish
            ->addData(['order' => (string) $OrderEvent->getMain()])
            ->addData(['profile' => (string) $this->getCurrentProfileUid()])
            ->send('orders');

        if($socket && $socket->isError())
        {
            return new JsonResponse($socket->getMessage());
        }

        return $this->render(
            [
                'id' => $id,
                'form' => $form->createView(),
                'order' => $OrderInfo,
                'history' => $History,
                'status' => $collection->from(($OrderInfo['order_status'] ?? 'new')),
                'statuses' => $collection,
            ]
        );
    }
}
