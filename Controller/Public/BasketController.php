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

namespace BaksDev\Orders\Order\Controller\Public;

use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\OrderDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\OrderForm;
use BaksDev\Orders\Order\UseCase\Public\Basket\OrderHandler;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;

#[AsController]
class BasketController extends AbstractController
{
    /** Корзина пользователя */
    private ?ArrayCollection $products = null;

    #[Route('/basket', name: 'public.basket')]
    public function index(
        Request $request,
        ProductUserBasketInterface $userBasket,
        OrderHandler $handler,
        AppCacheInterface $cache,
    ): Response
    {

        $AppCache = $cache->init('orders-order-basket');
        $key = md5($request->getClientIp().$request->headers->get('USER-AGENT'));

        $expires = 60 * 60; // Время кешировния 60 * 60 = 1 час

        if($this->getUsr())
        {
            $expires = 60 * 60 * 24; // Время кешировния 60 * 60 * 24 = 24 часа
        }

        /** Получаем корзину */
        if(!$AppCache->hasItem($key))
        {
            return $this->render(['form' => null]);
        }

        $this->products = ($AppCache->getItem($key))->get();

        if(null === $this->products)
        {
            $this->products = new ArrayCollection();
        }

        $OrderDTO = new OrderDTO();

        /** Присваиваем пользователя */
        $OrderUserDTO = $OrderDTO->getUsr();

        $OrderUserDTO->setUsr($this->getUsr()?->getId() ?: new UserUid());

        // Получаем продукцию, добавленную в корзину и присваиваем актуальные значения
        if(!$this->products->isEmpty())
        {
            /** @var OrderProductDTO $product */
            foreach($this->products as $product)
            {
                /** @var ProductUserBasketResult|false $ProductUserBasketResult */
                $ProductDetail = $userBasket
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->findAll();

                if(false === $ProductDetail)
                {
                    /**
                     * Удаляем из корзины, если карточка товара не найдена
                     * @var OrderProductDTO $element
                     */

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

                        return $this->redirectToReferer();
                    }
                }

                $product->setCard($ProductDetail);

                /** @var ProductUserBasketResult|false $ProductUserBasketResult */
                $ProductUserBasketResult = $userBasket
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->findAll();

                $OrderPriceDTO = $product->getPrice();
                $OrderPriceDTO->setPrice($ProductUserBasketResult->getProductPrice());
                $OrderPriceDTO->setCurrency($ProductUserBasketResult->getProductCurrency());

            }

            $OrderDTO->setProduct($this->products);
        }


        // Динамическая форма корзины
        $handleForm = $this->createForm(OrderForm::class, $OrderDTO);
        $handleForm->handleRequest($request);

        // Форма корзины
        $form = $this->createForm(
            type: OrderForm::class,
            data: $OrderDTO,
            options: [
                'action' => $this->generateUrl('orders-order:public.basket')
            ]);

        if(null === $request->headers->get('X-Requested-With'))
        {
            $form->handleRequest($request);
        }

        if($form->isSubmitted() && $form->isValid())
        {
            $this->refreshTokenForm($form);

            /** Делаем проверку геоданных */
            $OrderDeliveryDTO = $OrderUserDTO->getDelivery();
            $Latitude = $OrderDeliveryDTO->getLatitude();
            $Longitude = $OrderDeliveryDTO->getLongitude();

            if($Latitude === null || $Longitude === null)
            {
                $comment = 'Требуется уточнить адрес доставки!';

                if(!empty($OrderDTO->getComment()))
                {
                    $comment .= ' Комментарий клиента: '.$OrderDTO->getComment();
                }

                $OrderDTO->setComment($comment);
            }

            /**
             * Проверяем, что продукция в наличии в карточке
             */
            foreach($OrderDTO->getProduct() as $product)
            {

                /** @var ProductUserBasketResult|array $ProductDetail */
                $ProductDetail = $product->getCard();

                if(
                    false === $ProductDetail->getProductEvent()->equals($ProductDetail->getCurrentProductEvent()) ||
                    $product->getPrice()->getTotal() > $ProductDetail->getProductQuantity()
                )
                {

                    /**
                     * Удаляем из корзины продукцию
                     * @var OrderProductDTO $element
                     */
                    $predicat = static function($key, OrderProductDTO $element) use ($product) {
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
                            'К сожалению произошли некоторые изменения в продукции %s. Убедитесь в стоимости товара и его наличии, и добавьте товар в корзину снова.',
                            $ProductDetail['product_name']
                        ),
                    );

                    // Редирект на страницу товара
                    $postfix = (string) ($ProductDetail->getProductModificationPostfix() ?: $ProductDetail->getProductVariationPostfix() ?: $ProductDetail->getProductOfferPostfix() ?: null);

                    return $this->redirectToRoute('products-product:public.detail', [
                        'category' => (string) $ProductDetail->getCategoryUrl(),
                        'url' => (string) $ProductDetail->getProductUrl(),
                        'offer' => (string) $ProductDetail->getProductOfferValue(),
                        'variation' => (string) $ProductDetail->getProductVariationValue(),
                        'modification' => (string) $ProductDetail->getProductModificationValue(),
                        'postfix' => $postfix ? str_replace('/', '-', $postfix) : null

                    ]);
                }
            }

            $Order = $handler->handle($OrderDTO);

            if($Order instanceof Order)
            {
                $this->addFlash('success', 'user.order.new.success', 'user.order');

                // Удаляем кеш
                $AppCache->delete($key);

                return $this->redirectToRoute('orders-order:public.success', ['id' => $Order->getId()]);
            }

            $this->addFlash('danger', 'user.order.new.danger', 'user.order', $Order);
        }

        return $this->render([
            'form' => $form->createView(),
        ]);
    }
}
