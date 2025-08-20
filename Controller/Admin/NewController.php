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

declare(strict_types=1);

namespace BaksDev\Orders\Order\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Stocks\BaksDevMaterialsStocksBundle;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderHandler;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_NEW')]
final class NewController extends AbstractController
{
    #[Route('/admin/order/new', name: 'admin.new', methods: ['GET', 'POST'])]
    public function news(
        Request $request,
        NewOrderHandler $OrderHandler,
        ProductUserBasketInterface $userBasket,
    ): Response
    {
        $OrderDTO = new NewOrderDTO();

        /** Присваиваем заказу пользователя, который создал заказ */
        $OrderDTO->getInvariable()
            ->setUsr($this->getUsr()?->getId())
            ->setProfile($this->getProfileUid());

        // Форма
        $form = $this
            ->createForm(
                type: NewOrderForm::class,
                data: $OrderDTO,
                options: ['action' => $this->generateUrl('orders-order:admin.new'),],
            )
            ->handleRequest($request);

        $form->createView();

        if($form->isSubmitted() && $form->isValid() && $form->has('order_new'))
        {
            $this->refreshTokenForm($form);

            if($OrderDTO->getProduct()->isEmpty())
            {
                return $this->redirectToRoute('orders-order:admin.index');
            }

            foreach($OrderDTO->getProduct() as $product)
            {
                $ProductUserBasketResult = $userBasket
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->find();

                /** Редирект, если продукции не найдено */
                if(false === ($ProductUserBasketResult instanceof ProductUserBasketResult))
                {
                    return $this->redirectToRoute('orders-order:admin.index');
                }

                //                /**
                //                 * Если бизнес-модель не производство и на складе нет достаточного количества
                //                 */
                //                if(
                //                    false === class_exists(BaksDevMaterialsStocksBundle::class) &&
                //                    $product->getPrice()->getTotal() > $ProductUserBasketResult->getProductQuantity()
                //                )
                //                {
                //                    $this->addFlash(
                //                        'danger',
                //                        sprintf(
                //                            'К сожалению произошли некоторые изменения в продукции %s. Убедитесь в стоимости товара и его наличии, и добавьте товар в корзину снова.',
                //                            $ProductUserBasketResult->getProductName(),
                //                        ),
                //                    );
                //
                //                    return $this->redirectToRoute('orders-order:admin.index');
                //                }

                /** Присваиваем стоимость продукта в заказе */
                $product
                    ->getPrice()
                    ->setPrice($ProductUserBasketResult->getProductPrice())
                    ->setCurrency($ProductUserBasketResult->getProductCurrency());
            }

            $handle = $OrderHandler->handle($OrderDTO);

            $this->addFlash(
                'page.new',
                $handle instanceof Order ? 'success.new' : 'danger.new',
                'orders-order.admin',
                $handle instanceof Order ? $OrderDTO->getInvariable()->getNumber() : $handle,
            );

            if($handle instanceof Order)
            {
                return $this->redirectToRoute('orders-order:admin.index');
            }

        }

        return $this->render(['form' => $form->createView()]);
    }
}
