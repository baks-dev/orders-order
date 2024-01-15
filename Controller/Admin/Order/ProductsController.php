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
use BaksDev\Manufacture\Part\Repository\OpenManufacturePart\OpenManufacturePartInterface;
use BaksDev\Manufacture\Part\Type\Complete\ManufacturePartComplete;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\Repository\OrderProducts\OrderProductsInterface;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Wildberries\Manufacture\Repository\AllWbOrdersGroup\AllWbOrdersManufactureInterface;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterDTO;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

//use BaksDev\Manufacture\Part\Type\Marketplace\ManufacturePartMarketplace;
//use BaksDev\Wildberries\Manufacture\Type\Marketplace\ManufacturePartMarketplaceWildberries;
//use BaksDev\Wildberries\Orders\Forms\WbFilterProfile\ProfileFilterDTO;
//use BaksDev\Wildberries\Orders\Forms\WbFilterProfile\ProfileFilterForm;
//use BaksDev\Wildberries\Orders\Forms\WbFilterProfile\ProfileFilterFormAdmin;

#[AsController]
#[RoleSecurity('ROLE_ORDERS')]
final class ProductsController extends AbstractController
{
    /**
     * Список продукции для создания заказа
     */
    #[Route('/admin/order/products/{id}/{page<\d+>}', name: 'admin.order.products', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        Order $Order,
        OrderDetailInterface $orderDetail,

//        AllWbOrdersManufactureInterface $allWbOrdersGroup,
//        OpenManufacturePartInterface $openManufacturePart,
//        TokenUserGenerator $tokenUserGenerator,
        int $page = 0,
    ): Response
    {

        /**
         * Поиск
         */

        $search = new SearchDTO($request);
        $searchForm = $this->createForm(
            SearchForm::class, $search, [
                'action' => $this->generateUrl('orders-order:admin.order.products', ['id' => $Order->getId()]),
            ]
        );
        $searchForm->handleRequest($request);


//        /**
//         * Фильтр профиля пользователя
//         */
//
//        $profile = new ProfileFilterDTO($request, $this->getProfileUid());
//        $ROLE_ADMIN = $this->isGranted('ROLE_ADMIN');
//
//        if($ROLE_ADMIN)
//        {
//            $profileForm = $this->createForm(ProfileFilterFormAdmin::class, $profile, [
//                'action' => $this->generateUrl('wildberries-manufacture:admin.index'),
//            ]);
//        }
//        else
//        {
//            $profileForm = $this->createForm(ProfileFilterForm::class, $profile, [
//                'action' => $this->generateUrl('wildberries-manufacture:admin.index'),
//            ]);
//        }
//
//        $profileForm->handleRequest($request);
//        !$profileForm->isSubmitted()?:$this->redirectToReferer();



        // Получаем открытый черновик
        //$opens = $openManufacturePart->fetchOpenManufacturePartAssociative($this->getCurrentProfileUid());

        /**
         * Фильтр заказов
         */

        $filter = new WbOrdersProductFilterDTO($request);

//        if($opens)
//        {
//            /** Если открыт производственный процесс - жестко указываем категорию и скрываем выбор */
//            $filter->setCategory(new ProductCategoryUid($opens['category_id'], $opens['category_name']));
//        }


        $filterForm = $this->createForm(WbOrdersProductFilterForm::class, $filter, [
            'action' => $this->generateUrl('orders-order:admin.order.products', ['id' => $Order->getId()]),
        ]);
        $filterForm->handleRequest($request);
        !$filterForm->isSubmitted()?:$this->redirectToReferer();


        /**
         * Получаем список продукции в заказе
         */

        $OrderProducts = $orderDetail->fetchDetailOrderAssociative($Order->getId());


//        $WbOrders = $allWbOrdersGroup
//            ->fetchAllWbOrdersGroupAssociative(
//                $search,
//                $this->getProfileUid(),
//                $filter,
//                $opens ? new ManufacturePartComplete($opens['complete']) : null
//            );



        return $this->render(
            [
                //'opens' => $opens,
                'query' => $OrderProducts,
                'search' => $searchForm->createView(),
                //'profile' => $profileForm->createView(),
                'filter' => $filterForm->createView(),
                //'token' => $tokenUserGenerator->generate($this->getUsr()),
            ]
        );
    }
}