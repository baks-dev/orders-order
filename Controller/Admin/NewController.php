<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Order;
//use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderForm;
//use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderHandler;
//use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
#[RoleSecurity('ROLE_ORDER_NEW')]
final class NewController extends AbstractController
{
    #[Route('/admin/order/new', name: 'admin.new', methods: ['GET', 'POST'])]
    public function news(
        Request $request,
        NewOrderHandler $OrderHandler,
    ): Response
    {
        $OrderDTO = new NewOrderDTO();


        // Форма
        $form = $this->createForm(NewOrderForm::class, $OrderDTO, [
            'action' => $this->generateUrl('orders-order:admin.new'),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('order'))
        {
            $handle = $OrderHandler->handle($OrderDTO);

            $this->addFlash
            (
                'admin.page.new',
                $handle instanceof Order ? 'admin.success.new' : 'admin.danger.new',
                'admin.order',
                $handle
            );

            return $this->redirectToRoute('orders-order:admin.order.index');
        }

        return $this->render(['form' => $form->createView()]);
    }
}