<?php

declare(strict_types=1);

namespace BaksDev\Orders\Order\Controller\Admin\Order;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Forms\AddToOrder\AddToOrderForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use BaksDev\Orders\Order\Forms\AddToOrder\AddToOrderDTO;

#[AsController]
#[RoleSecurity('ROLE_ORDERS')]
final class AddController extends AbstractController
{
    #[Route('/admin/order/add', name: 'admin.order.add', methods: ['GET', 'POST',])]
    public function edit(Request $request): Response
    {
        $addForm = new AddToOrderDTO();

        $addForm = $this
            ->createForm(
                type: AddToOrderForm::class,
                data: $addForm,
                options: ['action' => $this->generateUrl('orders-order:admin.order.add')]
            )
            ->handleRequest($request);

        if($addForm->isSubmitted() && $addForm->isValid() && $addForm->has('order_add'))
        {
            $this->refreshTokenForm($addForm);
            return new JsonResponse(['valid' => true]);
        }

        return $this->render(['form' => $addForm->createView()]);
    }
}