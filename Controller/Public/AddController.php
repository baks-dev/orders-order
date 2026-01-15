<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Orders\Order\Repository\ProductEventBasket\ProductEventBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\PublicOrderProductDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\PublicOrderProductForm;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AsController]
class AddController extends AbstractController
{
    private CacheInterface $AppCache;

    private string $key;

    private ?ArrayCollection $products = null;

    /**
     * Добавить продукт в корзину
     */

    #[Route('/basket/add/', name: 'public.add')]
    public function index(
        Request $request,
        AppCacheInterface $cache,
        ProductEventBasketInterface $productEvent,
        ProductUserBasketInterface $productDetail,

        #[ParamConverter(ProductEventUid::class, key: 'product')] $event,
        #[ParamConverter(ProductOfferUid::class)] $offer = null,
        #[ParamConverter(ProductVariationUid::class)] $variation = null,
        #[ParamConverter(ProductModificationUid::class)] $modification = null,
        #[Autowire(env: 'HOST')] string|null $HOST = null,
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

        $AddProductBasketDTO = new PublicOrderProductDTO()
            ->setProduct($event)
            ->setOffer($offer)
            ->setVariation($variation)
            ->setModification($modification);

        $ProductDetail = $productDetail
            ->forEvent($AddProductBasketDTO->getProduct())
            ->forOffer($AddProductBasketDTO->getOffer())
            ->forVariation($AddProductBasketDTO->getVariation())
            ->forModification($AddProductBasketDTO->getModification())
            ->find();

        $this->AppCache = $cache->init('orders-order-basket');
        $this->key = md5($HOST.$request->getClientIp().$request->headers->get('USER-AGENT'));
        $expires = 60 * 60; // Время кешировния 1 час - для не аутентифицированного пользователя

        if($this->getUsr())
        {
            $expires = 60 * 60 * 24; // Время кешировния 24 часа - для аутентифицированного пользователя
        }

        // Получаем кеш
        if($this->AppCache->hasItem($this->key))
        {
            $this->products = $this->AppCache->getItem($this->key)->get();

            /** Если в запросе не было передано body - не реагируем */
            if(false === $this->products->isEmpty() && false === empty($request->getContent()))
            {
                /** @var PublicOrderProductDTO|null $product */
                $product = $this->products->findFirst(
                    function(int $k, PublicOrderProductDTO $PublicOrderProductDTO) use ($AddProductBasketDTO) {

                        return $PublicOrderProductDTO->getProduct()->equals($AddProductBasketDTO->getProduct())
                            && ((is_null($PublicOrderProductDTO->getOffer()) === true && is_null($AddProductBasketDTO->getOffer()) === true) || $PublicOrderProductDTO->getOffer()?->equals($AddProductBasketDTO->getOffer()))
                            && ((is_null($PublicOrderProductDTO->getVariation()) === true && is_null($AddProductBasketDTO->getVariation()) === true) || $PublicOrderProductDTO->getVariation()?->equals($AddProductBasketDTO->getVariation()))
                            && ((is_null($PublicOrderProductDTO->getModification()) === true && is_null($AddProductBasketDTO->getModification()) === true) || $PublicOrderProductDTO->getModification()?->equals($AddProductBasketDTO->getModification()));
                    },
                );

                if(true === ($product instanceof PublicOrderProductDTO))
                {
                    $isTotalModify = $this->totalModify($product, $ProductDetail, $request->toArray());

                    if(true === $isTotalModify)
                    {
                        return new JsonResponse(
                            [
                                'type' => 'success',
                                'header' => 'Количество товара успешно изменено',
                                'message' => $product->getPrice()->getTotal(),
                            ],
                            200,
                        );
                    }
                }
            }
        }

        $form = $this
            ->createForm(
                PublicOrderProductForm::class,
                $AddProductBasketDTO,
                [
                    'action' => $this->generateUrl(
                        'orders-order:public.add',
                        [
                            'product' => $AddProductBasketDTO->getProduct(),
                            'offer' => $AddProductBasketDTO->getOffer(),
                            'variation' => $AddProductBasketDTO->getVariation(),
                            'modification' => $AddProductBasketDTO->getModification(),

                        ],
                    ),
                ],
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $this->refreshTokenForm($form);

            if($this->products === null)
            {
                $this->products = new ArrayCollection();
            }

            /** @var PublicOrderProductDTO $element */
            $predicat = function($key, PublicOrderProductDTO $element) use ($AddProductBasketDTO) {
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
                        'href' => $this->generateUrl('orders-order:public.basket'),
                        'name' => 'перейти в корзину',
                        'status' => 400,
                    ],
                    400,
                );
            }

            // Удаляем кеш
            $this->AppCache->delete($this->key);

            // получаем кеш
            $this->AppCache->get($this->key, function(ItemInterface $item) use ($AddProductBasketDTO, $expires) {
                $item->expiresAfter($expires);
                $this->products->add($AddProductBasketDTO);

                return $this->products;
            });

            return new JsonResponse(
                [
                    'type' => 'success',
                    'header' => $Event->getOption(),
                    'message' => 'Товар успешно добавлен.',
                    'href' => $this->generateUrl('orders-order:public.basket'),
                    'name' => 'перейти в корзину',
                    'status' => 200,
                ],
                200,
            );
        }

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
            400,
        );
    }

    /** Модифицируем количество продукта в корзине */
    private function totalModify(
        PublicOrderProductDTO $product,
        ProductUserBasketResult $ProductDetail,
        array $body
    ): bool
    {
        $cacheItem = $this->AppCache->getItem($this->key);

        if(true === isset($body['action']))
        {
            $action = $body['action'];

            $modifyTotal = null;

            /** Изменяем количество */
            if($action === 'change' && true === isset($body['quantity']))
            {
                $quantity = $body['quantity'];

                /** Изменяем количество если оно в диапазоне от min до max */
                if(
                    $quantity >= $ProductDetail->getCategoryMinimal()
                    && $quantity <= $ProductDetail->getProductQuantity()
                )
                {
                    $modifyTotal = $quantity;
                }
            }

            /** Уменьшаем пока общее количество больше минимального количества */
            if($action === 'minus' && $product->getPrice()->getTotal() > $ProductDetail->getCategoryMinimal())
            {
                $modifyTotal = $product->getPrice()->getTotal() - 1;
            }

            /** Увеличиваем пока общее количество меньше наличия */
            if($action === 'plus' && $product->getPrice()->getTotal() < $ProductDetail->getProductQuantity())
            {
                $modifyTotal = $product->getPrice()->getTotal() + 1;
            }

            /** Если было изменение цены - пересохраняем кэш */
            if(null !== $modifyTotal)
            {
                $product->getPrice()->setTotal($modifyTotal);
                $cacheItem->set($this->products);
                $this->AppCache->save($cacheItem);

                return true;
            }
        }

        return false;
    }
}
