<?php

namespace BaksDev\Orders\Order\Controller\User;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_USER')]
class OrderDetailController extends AbstractController
{
    /**
     * Используется для получения данных о товарах в списке заказов
     */

    #[Route('/order/detail/{id}', name: 'user.orders.detail')]
    public function index(
        Request $request,
        #[MapEntity] Order $Order,
        OrderDetailInterface $orderDetail,

    )
    {

        $OrderInfo = $orderDetail->onOrder($Order->getId())->find();

        return $this->render([
            'products' => $OrderInfo->getOrderProducts()
        ]);

    }
}