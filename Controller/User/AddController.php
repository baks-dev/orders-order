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

use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Orders\Order\Repository\ProductEventBasket\ProductEventBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\UseCase\User\Basket\Add\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\User\Basket\Add\OrderProductForm;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;

#[AsController]
class AddController extends AbstractController
{
    private ?ArrayCollection $products = null;

    /**
     * Добавить продукт в корзину
     */

    #[Route('/basket/add/', name: 'user.add')]
    public function index(
        Request $request,
        AppCacheInterface $cache,
        ProductEventBasketInterface $productEvent,
        ProductUserBasketInterface $productDetail,
        #[ParamConverter(ProductEventUid::class, key: 'product')] $event,
        #[ParamConverter(ProductOfferUid::class)] $offer = null,
        #[ParamConverter(ProductVariationUid::class)] $variation = null,
        #[ParamConverter(ProductModificationUid::class)] $modification = null,
    ): Response
    {

        /** Проверяем обязательное значение event */
        if(empty($event))
        {
            return $this->ErrorResponse();
        }

        /** Проверяем обязательное значение offer если передан variation */
        if(!empty($variation) && empty($offer))
        {
            return $this->ErrorResponse();
        }

        /** Проверяем обязательное значение variation если передан modification */
        if(!empty($modification) && empty($variation))
        {
            return $this->ErrorResponse();
        }

        /** Получаем событие продукта по переданным параметрам */
        $Event = $productEvent->getOneOrNullProductEvent($event, $offer, $variation, $modification);

        if(!$Event)
        {
            return $this->ErrorResponse();
        }

        $AddProductBasketDTO = new OrderProductDTO();

        $AddProductBasketDTO->setProduct($event);
        $AddProductBasketDTO->setOffer($offer);
        $AddProductBasketDTO->setVariation($variation);
        $AddProductBasketDTO->setModification($modification);

        $form = $this
            ->createForm(
                OrderProductForm::class,
                $AddProductBasketDTO,
                [
                    'action' => $this->generateUrl(
                        'orders-order:user.add',
                        [
                            'product' => $AddProductBasketDTO->getProduct(),
                            'offer' => $AddProductBasketDTO->getOffer(),
                            'variation' => $AddProductBasketDTO->getVariation(),
                            'modification' => $AddProductBasketDTO->getModification(),

                        ]
                    ),
                ]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $this->refreshTokenForm($form);

            $AppCache = $cache->init('orders-order-basket');
            $key = md5($request->getClientIp().$request->headers->get('USER-AGENT'));
            $expires = 60 * 60; // Время кешировния 60 * 60 = 1 час

            if($this->getUsr())
            {
                $expires = 60 * 60 * 24; // Время кешировния 60 * 60 * 24 = 24 часа
            }

            // Получаем кеш
            if($AppCache->hasItem($key))
            {
                $this->products = ($AppCache->getItem($key))->get();
            }

            if($this->products === null)
            {
                $this->products = new ArrayCollection();
            }

            /** @var OrderProductDTO $element */
            $predicat = function($key, OrderProductDTO $element) use ($AddProductBasketDTO) {
                return
                    $element->getProduct()->equals($AddProductBasketDTO->getProduct())
                    && (!$AddProductBasketDTO->getOffer() || $element->getOffer()?->equals($AddProductBasketDTO->getOffer()))
                    && (!$AddProductBasketDTO->getVariation() || $element->getVariation()?->equals($AddProductBasketDTO->getVariation()))
                    && (!$AddProductBasketDTO->getModification() || $element->getModification()?->equals($AddProductBasketDTO->getModification()));
            };

            if($this->products->exists($predicat))
            {
                return new JsonResponse(
                    [
                        'type' => 'success',
                        'header' => $Event->getOption(),
                        'message' => 'Товар уже добавлен в корзину',
                        'href' => $this->generateUrl('orders-order:user.basket'),
                        'name' => 'перейти в корзину',
                        'status' => 400,
                    ],
                    400
                );
            }

            // Удаляем кеш
            $AppCache->delete($key);

            // получаем кеш
            $AppCache->get($key, function(ItemInterface $item) use ($AddProductBasketDTO, $expires) {
                $item->expiresAfter($expires);
                $this->products->add($AddProductBasketDTO);

                return $this->products;
            });

            return new JsonResponse(
                [
                    'type' => 'success',
                    'header' => $Event->getOption(),
                    'message' => 'Товар успешно добавлен.',
                    'href' => $this->generateUrl('orders-order:user.basket'),
                    'name' => 'перейти в корзину',
                    'status' => 200,
                ],
                200
            );
        }

        $ProductDetail = $productDetail
            ->forEvent($AddProductBasketDTO->getProduct())
            ->forOffer($AddProductBasketDTO->getOffer())
            ->forVariation($AddProductBasketDTO->getVariation())
            ->forModification($AddProductBasketDTO->getModification())
            ->findAll();

        return $this->render([
            'form' => $form->createView(),
            'name' => $Event->getOption(),
            'card' => $ProductDetail,
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
