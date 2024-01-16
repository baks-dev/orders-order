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

namespace BaksDev\Orders\Order\Controller\Admin\Order;

use BaksDev\Centrifugo\Services\Token\TokenUserGenerator;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Repository\OrderDraft\OpenOrderDraftInterface;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterForm;
use BaksDev\Products\Product\Repository\AllExistsProducts\AllExistsProductsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_NEW')]
final class DraftController extends AbstractController
{
    #[Route('/admin/order/draft/{page<\d+>}', name: 'admin.order.draft', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        OpenOrderDraftInterface $draft,
        AllExistsProductsInterface $getAllProduct,
        TokenUserGenerator $tokenUserGenerator,
        int $page = 0,
    ): Response
    {

        /**
         * Получаем получаем открытый активный черновик ответственного (Независимо от авторизации)
         */
        $opens = $draft->getOpenDraft($this->getProfileUid());

        /**
         * Фильтр продукции по ТП
         */
        $filter = new ProductFilterDTO($request);
        $filterForm = $this->createForm(ProductFilterForm::class, $filter, [
            'action' => $this->generateUrl('orders-order:admin.order.draft'),
        ]);

        $filterForm->handleRequest($request);
        !$filterForm->isSubmitted() ?: $this->redirectToReferer();


        // Поиск
        $search = new SearchDTO($request);
        $searchForm = $this->createForm(SearchForm::class, $search, [
            'action' => $this->generateUrl('orders-order:admin.order.draft'),
        ]);
        $searchForm->handleRequest($request);

        // Получаем список торговых предложений
        $query = $getAllProduct
            ->search($search)
            ->filter($filter)
            ->getAllProducts();


        return $this->render(
            [
                'opens' => $opens,
                'query' => $query,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
                //'profile' => $profileForm->createView(),
                'token' => $tokenUserGenerator->generate($this->getUsr()),
            ]
        );
    }
}
