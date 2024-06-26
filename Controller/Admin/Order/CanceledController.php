<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Controller\Admin\Order;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\UseCase\Admin\Canceled\OrderCanceledDTO;
use BaksDev\Orders\Order\UseCase\Admin\Canceled\OrderCanceledForm;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_STATUS')]
final class CanceledController extends AbstractController
{
    /** Отмена заказа с указанием причины */
    #[Route('/admin/order/canceled/{id}', name: 'admin.order.canceled', methods: ['GET', 'POST'])]
    public function canceled(
        Request $request,
        #[MapEntity] Order $Order,
        EntityManagerInterface $entityManager,
        CentrifugoPublishInterface $publish,
        OrderStatusHandler $OrderStatusHandler
    ): Response {

        /**
         * Отправляем сокет для скрытия заказа у других менеджеров
         */
        $publish
            ->addData(['order' => (string) $Order->getId()])
            ->addData(['profile' => (string) $this->getProfileUid()])
            ->send('orders');


        $OrderEvent = $entityManager->getRepository(OrderEvent::class)->find($Order->getEvent());

        if(!$OrderEvent)
        {
            return $this->redirectToReferer();
        }

        $OrderCanceledDTO = new OrderCanceledDTO($this->getProfileUid());
        $OrderEvent->getDto($OrderCanceledDTO);

        $form = $this->createForm(OrderCanceledForm::class, $OrderCanceledDTO, [
            'action' => $this->generateUrl('orders-order:admin.order.canceled', ['id' => $Order->getId()]),
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('order_cancel'))
        {
            $this->refreshTokenForm($form);

            $handle = $OrderStatusHandler->handle($OrderCanceledDTO);

            $this->addFlash(
                'page.cancel',
                $handle instanceof Order ? 'success.cancel' : 'danger.cancel',
                'orders-order.admin',
                $handle
            );

            return $this->redirectToReferer();  // отмена заказа может происходить в других разделах
        }

        return $this->render([
            'form' => $form->createView(),
            'number' => $Order->getNumber()
        ]);
    }
}
