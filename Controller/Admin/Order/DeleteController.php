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

declare(strict_types=1);

namespace BaksDev\Orders\Order\Controller\Admin\Order;


use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\UseCase\Admin\Delete\OrderDeleteDTO;
use BaksDev\Orders\Order\UseCase\Admin\Delete\OrderDeleteForm;
use BaksDev\Orders\Order\UseCase\Admin\Delete\OrderDeleteHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_STATUS')]
final class DeleteController extends AbstractController
{
    #[Route('/admin/order/delete/{id}', name: 'admin.order.delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity] OrderEvent $OrderEvent,
        OrderDeleteHandler $OrderDeleteHandler,
    ): Response
    {

        $OrderCancelDTO = new OrderDeleteDTO($this->getProfileUid());
        $OrderEvent->getDto($OrderCancelDTO);
        $form = $this->createForm(OrderDeleteForm::class, $OrderCancelDTO, [
            'action' => $this->generateUrl('orders-order:admin.order.delete', ['id' => $OrderCancelDTO->getEvent()]),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('order_delete'))
        {
            $handle = $OrderDeleteHandler->handle($OrderCancelDTO);

            $this->addFlash
            (
                'page.delete',
                $handle instanceof Order ? 'success.delete' : 'danger.delete',
                'orders-order.admin',
                $handle
            );

            return $this->redirectToRoute('orders-order:admin.index');
        }

        return $this->render([
            'form' => $form->createView(),
        ]);
    }
}
