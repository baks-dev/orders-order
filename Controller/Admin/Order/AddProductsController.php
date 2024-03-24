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


use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Repository\ExistsOrderProduct\ExistsOrderProductInterface;
use BaksDev\Orders\Order\Repository\ProductEventBasket\ProductEventBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\UseCase\Admin\AddProduct\OrderAddProductsDTO;
use BaksDev\Orders\Order\UseCase\Admin\AddProduct\OrderAddProductsForm;
use BaksDev\Orders\Order\UseCase\Admin\AddProduct\OrderAddProductsHandler;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_NEW')]
final class AddProductsController extends AbstractController
{
    /**
     * Добавить продукцию в заказ
     */
    #[Route('/admin/order/product/add/{total}', name: 'admin.order.add', methods: ['GET', 'POST'])]
    public function news(
        Request $request,
        OrderAddProductsHandler $OrderAddProductsHandler,
        CentrifugoPublishInterface $CentrifugoPublish,

        ProductEventBasketInterface $productEvent,
        ProductUserBasketInterface $productDetail,
        ExistsOrderProductInterface $existsOrderProduct,

        #[ParamConverter(ProductEventUid::class)] $product = null,
        #[ParamConverter(ProductOfferUid::class)] $offer = null,
        #[ParamConverter(ProductVariationUid::class)] $variation = null,
        #[ParamConverter(ProductModificationUid::class)] $modification = null,
        ?int $total = null,
    ): Response
    {

        /** Получаем событие продукта по переданным параметрам */
        $Event = $productEvent->getOneOrNullProductEvent($product, $offer, $variation, $modification);

        if(!$Event)
        {
            return $this->ErrorResponse();
        }

        $AddProductBasketDTO = new OrderAddProductsDTO();

        $AddProductBasketDTO->setProduct($product);
        $AddProductBasketDTO->setOffer($offer);
        $AddProductBasketDTO->setVariation($variation);
        $AddProductBasketDTO->setModification($modification);


        $ProductDetail = $productDetail->fetchProductBasketAssociative(
            $AddProductBasketDTO->getProduct(),
            $AddProductBasketDTO->getOffer(),
            $AddProductBasketDTO->getVariation(),
            $AddProductBasketDTO->getModification()
        );


        // Форма
        $form = $this->createForm(OrderAddProductsForm::class, $AddProductBasketDTO, [
            'action' => $this->generateUrl('orders-order:admin.order.add',
                [
                    'product' => $AddProductBasketDTO->getProduct(),
                    'offer' => $AddProductBasketDTO->getOffer(),
                    'variation' => $AddProductBasketDTO->getVariation(),
                    'modification' => $AddProductBasketDTO->getModification(),
                ]
            ),
        ]);


        $form->handleRequest($request);


        if($form->isSubmitted() && $form->isValid() && $form->has('order_products'))
        {

            $isExists = $existsOrderProduct->isExistsProductDraft($this->getProfileUid(), $product, $offer, $variation, $modification);

            if($isExists)
            {
                $this->addFlash(
                    'page.add',
                    'Продукция уже имеется в заказе',
                    'orders-order.admin');

                return $this->redirectToRoute('orders-order:admin.order.draft', status: $request->isXmlHttpRequest() ? 200 : 302);
            }

            $OrderPriceDTO = $AddProductBasketDTO->getPrice();
            $OrderPriceDTO->setPrice(new Money($ProductDetail['product_price'] / 100));
            $OrderPriceDTO->setCurrency(new Currency($ProductDetail['product_currency']));

            $handle = $OrderAddProductsHandler->handle($AddProductBasketDTO, $this->getProfileUid());

            /** Если был добавлен продукт в открытую партию отправляем сокет */
            if($handle instanceof OrderEvent)
            {
                $ProductDetail['product_total'] = $AddProductBasketDTO->getPrice()->getTotal();

                $CentrifugoPublish

                    ->addData(['product' => $this->render(['card' => $ProductDetail], file: 'centrifugo.html.twig')->getContent()]) // HTML шаблон
                    ->addData(['total' => $ProductDetail['product_total']]) // количество для суммы всех товаров
                    ->send((string) $handle->getId())

                    ->addData(['identifier' => $AddProductBasketDTO->getIdentifier()]) // ID карточки
                    ->send('remove')
                ;

                $return = $this->addFlash(
                    type: 'page.add',
                    message: 'success.add',
                    domain: 'orders-order.admin',
                    status: $request->isXmlHttpRequest() ? 200 : 302 // не делаем редирект в случае AJAX
                );

                return $request->isXmlHttpRequest() ? $return : $this->redirectToRoute('orders-order:admin.order.draft');
            }

            $this->addFlash(
                'page.add',
                'danger.add',
                'orders-order.admin',
                $handle);



            return $this->redirectToRoute('orders-order:admin.order.draft', status: $request->isXmlHttpRequest() ? 200 : 302);
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