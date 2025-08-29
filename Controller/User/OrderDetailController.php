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
use BaksDev\Materials\Sign\Repository\GroupMaterialSignsByOrder\GroupMaterialSignsByOrderInterface;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Products\Sign\Repository\GroupProductSignsByOrder\GroupProductSignsByOrderInterface;
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
        ?GroupMaterialSignsByOrderInterface $GroupMaterialSignsByOrder = null,
        ?GroupProductSignsByOrderInterface $GroupProductSignsByOrder = null,
    )
    {

        $OrderInfo = $orderDetail->onOrder($Order->getId())->find();

        $MaterialSign = false;

        if($GroupMaterialSignsByOrder)
        {
            $MaterialSign = $GroupMaterialSignsByOrder
                ->forOrder($Order)
                ->findAll();

            if(false === $MaterialSign || false === $MaterialSign->valid())
            {
                $MaterialSign = false;
            }
        }

        $ProductSign = false;

        if($GroupProductSignsByOrder)
        {
            $ProductSign = $GroupProductSignsByOrder
                ->forOrder($Order)
                ->findAll();

            if(false === $ProductSign || false === $ProductSign->valid())
            {
                $ProductSign = false;
            }
        }

        return $this->render([
            'order' => $OrderInfo,
            'materials_sign' => $MaterialSign,
            'products_sign' => $ProductSign,
        ]);

    }
}