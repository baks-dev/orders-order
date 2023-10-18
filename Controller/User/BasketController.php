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

namespace BaksDev\Orders\Order\Controller\User;

use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\UseCase\User\Basket\Add\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\User\Basket\OrderDTO;
use BaksDev\Orders\Order\UseCase\User\Basket\OrderForm;
use BaksDev\Orders\Order\UseCase\User\Basket\OrderHandler;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;

#[AsController]
class BasketController extends AbstractController
{
    // Корзина пользователя

    private ?ArrayCollection $products = null;

    #[Route('/basket', name: 'user.basket')]
    public function index(
        Request $request,
        ProductUserBasketInterface $userBasket,
        OrderHandler $handler,
        AppCacheInterface $cache,
    ): Response
    {

        $AppCache = $cache->init('orders-order');
        $key = md5($request->getClientIp().$request->headers->get('USER-AGENT'));

        $expires = 60 * 60; // Время кешировния 60 * 60 = 1 час

        if($this->getUsr())
        {
            $expires = 60 * 60 * 24; // Время кешировния 60 * 60 * 24 = 24 часа
        }

        // Получаем корзину

        if($AppCache->hasItem($key))
        {
            $this->products = ($AppCache->getItem($key))->get();
        }

        if(null === $this->products)
        {
            $this->products = new ArrayCollection();
        }

        $OrderDTO = new OrderDTO();

        /** Присваиваем пользователя */
        $OrderUserDTO = $OrderDTO->getUsers();
        $OrderUserDTO->setUsr($this->getUsr()?->getId());

        // Получаем продукцию, добавленную в корзину и присваиваем актуальные значения
        if(!$this->products->isEmpty())
        {
            /** @var OrderProductDTO $product */
            foreach($this->products as $product)
            {
                $ProductDetail = $userBasket->fetchProductBasketAssociative(
                    $product->getProduct(),
                    $product->getOffer(),
                    $product->getVariation(),
                    $product->getModification()
                );

                if($ProductDetail)
                {
                    // Если событие продукта изменилось - удаляем из корзины
                    if($ProductDetail['event'] !== $ProductDetail['current_event'])
                    {
                        /** @var OrderProductDTO $element */
                        $predicat = function($key, OrderProductDTO $element) use ($product) {
                            return $element === $product;
                        };

                        $removeElement = $this->products->findFirst($predicat);

                        if($removeElement)
                        {
                            // Удаляем кеш
                            $AppCache->delete($key);

                            // Запоминаем новый кеш
                            $AppCache->get($key, function(ItemInterface $item) use ($removeElement, $expires) {
                                $item->expiresAfter($expires);
                                $this->products->removeElement($removeElement);

                                return $this->products;
                            });
                        }

                        $this->addFlash(
                            'danger',
                            sprintf(
                                'К сожалению произошли некоторые изменения в продукции %s. Убедителсь в стоимости товара и его наличии, и добавьте товар в корзину снова.',
                                $ProductDetail['product_name']
                            ),
                        );

                        // Редирект на страницу товара
                        return $this->redirectToRoute('products-product:user.detail', [
                            'url' => $ProductDetail['product_url'],
                            'offer' => $ProductDetail['product_offer_value'],
                            'variation' => $ProductDetail['product_variation_value'],
                            'modification' => $ProductDetail['product_modification_value'],
                        ]);
                    }

                    $product->setCard($ProductDetail);

                    $OrderPriceDTO = $product->getPrice();
                    $OrderPriceDTO->setPrice(new Money($ProductDetail['product_price'] / 100));
                    $OrderPriceDTO->setCurrency(new Currency($ProductDetail['product_currency']));
                }
            }

            $OrderDTO->setProduct($this->products);
        }


        // Динамическая форма корзины
        $handleForm = $this->createForm(OrderForm::class, $OrderDTO);
        $handleForm->handleRequest($request);

        // Форма форма корзины
        $form = $this->createForm(OrderForm::class, $OrderDTO, ['action' => $this->generateUrl('orders-order:user.basket')]);

        if(null === $request->headers->get('X-Requested-With'))
        {
            $form->handleRequest($request);
        }

        if($form->isSubmitted() && $form->isValid())
        {
            $Order = $handler->handle($OrderDTO);

            if($Order instanceof Order)
            {
                $this->addFlash('success', 'user.order.new.success', 'user.order');

                // Удаляем кеш
                $AppCache->delete($key);

                return $this->redirectToRoute('orders-order:user.basket');
            }

            $this->addFlash('danger', 'user.order.new.danger', 'user.order', $Order);

            // return $this->redirectToRoute('orders-order:user.basket');
        }

        return $this->render([
            'form' => $form->createView(),
        ]);
    }
}
