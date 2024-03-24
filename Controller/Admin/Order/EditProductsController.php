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


use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\UseCase\Admin\AddProduct\OrderAddProductsDTO;
use BaksDev\Orders\Order\UseCase\Admin\AddProduct\OrderAddProductsForm;
use BaksDev\Orders\Order\UseCase\Admin\AddProduct\OrderAddProductsHandler;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_NEW')]
final class EditProductsController extends AbstractController
{
    /**
     * Редактировать продукцию
     */
    #[Route('/admin/order/product/edit/{id}', name: 'admin.order.edit', methods: ['GET', 'POST'])]
    public function news(
        #[MapEntity] OrderProduct $OrderProduct,
        Request $request,
        OrderAddProductsHandler $OrderAddProductsHandler,
        ProductUserBasketInterface $productDetail,
    ): Response
    {

        /** @var OrderAddProductsDTO $OrderAddProductsDTO */
        $OrderAddProductsDTO = $OrderProduct->getDto(OrderAddProductsDTO::class);

        $ProductDetail = $productDetail->fetchProductBasketAssociative(
            $OrderAddProductsDTO->getProduct(),
            $OrderAddProductsDTO->getOffer(),
            $OrderAddProductsDTO->getVariation(),
            $OrderAddProductsDTO->getModification()
        );


        // Форма
        $form = $this->createForm(OrderAddProductsForm::class, $OrderAddProductsDTO, [
            'action' => $this->generateUrl('orders-order:admin.order.edit',
                ['id' => $OrderAddProductsDTO->getOrderProductId()]
            ),
        ]);


        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('order_products'))
        {

            $OrderPriceDTO = $OrderAddProductsDTO->getPrice();
            $OrderPriceDTO->setPrice(new Money($ProductDetail['product_price'] / 100));
            $OrderPriceDTO->setCurrency(new Currency($ProductDetail['product_currency']));

            $handle = $OrderAddProductsHandler->handle($OrderAddProductsDTO, $this->getProfileUid());

            $this->addFlash
            (
                'page.new',
                $handle instanceof OrderEvent ? 'success.new' : 'danger.new',
                'orders-order.admin',
                $handle
            );


            //return $this->redirectToRoute('orders-order:admin.order.draft');
            return $this->redirectToReferer();
        }

        return $this->render([
            'form' => $form->createView(),
            'card' => $ProductDetail
        ]);
    }


    public function ErrorResponse(): JsonResponse
    {
        return new JsonResponse(
            [
                'type' => 'danger',
                'header' => 'Корзина',
                'message' => 'Ошибка при добавлении товара в корзину',
                'status' => 400,
            ],
            400
        );
    }
}