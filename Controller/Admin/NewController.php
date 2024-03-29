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
use BaksDev\Orders\Order\Repository\OrderDraft\OpenOrderDraftInterface;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_NEW')]
final class NewController extends AbstractController
{
    #[Route('/admin/order/new', name: 'admin.new', methods: ['GET', 'POST'])]
    public function news(
        Request $request,
        OpenOrderDraftInterface $draft,
        NewOrderHandler $OrderHandler
    ): Response
    {

        if($draft->existsOpenDraft($this->getProfileUid()))
        {
            return $this->redirectToRoute('orders-order:admin.order.draft');
        }


        $OrderDTO = new NewOrderDTO($this->getProfileUid());

        // Форма
        $form = $this->createForm(NewOrderForm::class, $OrderDTO, [
            'action' => $this->generateUrl('orders-order:admin.new'),
        ]);

        $form->handleRequest($request);
        $form->createView();


        if($form->isSubmitted() && $form->isValid() && $form->has('draft'))
        {
            $handle = $OrderHandler->handle($OrderDTO);

            $this->addFlash
            (
                'page.new',
                $handle instanceof Order ? 'success.new' : 'danger.new',
                'orders-order.admin',
                $handle
            );

            if($handle instanceof Order)
            {
                return $this->redirectToRoute('orders-order:admin.order.draft');
            }

        }

        return $this->render(['form' => $form->createView()]);
    }
}

