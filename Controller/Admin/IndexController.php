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

use BaksDev\Centrifugo\Services\Token\TokenUserGenerator;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Forms\DeliveryFilter\OrderDeliveryFilterDTO;
use BaksDev\Orders\Order\Forms\DeliveryFilter\OrderDeliveryFilterForm;
use BaksDev\Orders\Order\Repository\AllOrders\AllOrdersInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS')]
final class IndexController extends AbstractController
{
    /**
     * Управление заказами (Канбан)
     */
    #[Route('/admin/orders/{page<\d+>}', name: 'admin.index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AllOrdersInterface $allOrders,
        OrderStatusCollection $collection,
        TokenUserGenerator $tokenUserGenerator,
        int $page = 0,
    ): Response
    {

        // Поиск
        $search = new SearchDTO();

        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: ['action' => $this->generateUrl('orders-order:admin.index')]
            )
            ->handleRequest($request);


        /** Фильтр по способу доставки */
        $OrderDeliveryFilterDTO = new OrderDeliveryFilterDTO();

        $OrderDeliveryFilterForm = $this
            ->createForm(
                type: OrderDeliveryFilterForm::class,
                data: $OrderDeliveryFilterDTO,
                options: ['action' => $this->generateUrl('orders-order:admin.index')]
            )
            ->handleRequest($request);

        $orders = null;

        /** @var OrderStatus $status */
        foreach(OrderStatus::cases() as $status)
        {
            if($status->equals('canceled'))
            {
                continue;
            }

            if($status->equals('completed'))
            {
                $allOrders->setLimit(10);
            }

            // Получаем список
            $orders[$status->getOrderStatusValue()] = $allOrders
                ->search($search)
                ->status($status)
                ->filter($OrderDeliveryFilterDTO)
                ->findPaginator($this->getProfileUid())
                ->getData();
        }

        return $this->render(
            [
                'query' => $orders,
                'status' => $collection->cases(),
                'token' => $tokenUserGenerator->generate($this->getUsr()),
                'current_profile' => $this->getCurrentProfileUid(),

                'search' => $searchForm->createView(),
                'filter' => $OrderDeliveryFilterForm->createView(),
            ]
        );
    }
}
