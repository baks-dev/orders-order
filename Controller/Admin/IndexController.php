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

namespace BaksDev\Orders\Order\Controller\Admin;

// use App\Module\Product\Repository\Product\AllProduct;
use BaksDev\Centrifugo\Services\Token\TokenUserGenerator;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Repository\AllOrders\AllOrdersInterface;
use BaksDev\Orders\Order\Repository\OrderDraft\OpenOrderDraftInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

// use App\System\Form\Search\Command;

#[AsController]
#[RoleSecurity('ROLE_ORDERS')]
final class IndexController extends AbstractController
{
    #[Route('/admin/orders/{page<\d+>}', name: 'admin.index', methods: [
        'GET',
        'POST',
    ])]
    public function index(
        Request $request,
        OpenOrderDraftInterface $draft,
        AllOrdersInterface $allOrders,
        OrderStatusCollection $collection,
        TokenUserGenerator $tokenUserGenerator,
        int $page = 0,
    ): Response {

        // Поиск
        $search = new SearchDTO();
        $searchForm = $this->createForm(SearchForm::class, $search);
        $searchForm->handleRequest($request);

        $orders = null;

        /** @var OrderStatus $status */
        foreach (OrderStatus::cases() as $status) {

            if ($status->equals('canceled') || $status->equals('draft')) {
                continue;
            }

            // Получаем список
            $orders[$status->getOrderStatusValue()] = $allOrders
                ->search($search)
                ->status($status)
                ->fetchAllOrdersAssociative($this->getProfileUid())->getData();

            //dd(end($orders));

        }

        return $this->render(
            [
                'query' => $orders,
                'opens' => $draft->existsOpenDraft($this->getProfileUid()),
                'status' => $collection->cases(),
                'token' => $tokenUserGenerator->generate($this->getUsr()),
                'search' => $searchForm->createView(),
            ]
        );
    }
}
