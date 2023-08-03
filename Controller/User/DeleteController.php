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

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Orders\Order\Repository\ProductEventBasket\ProductEventBasketInterface;
use BaksDev\Orders\Order\UseCase\User\Basket\Add\OrderProductDTO;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
class DeleteController extends AbstractController
{
    private ?ArrayCollection $products = null;

    // Удаляет товар из корзины
    #[Route('/basket/delete', name: 'user.delete')]
    public function index(
        Request $request,
        ProductEventBasketInterface $productEvent,
        TranslatorInterface $translator,
        #[ParamConverter(ProductEventUid::class)]        $product,
        #[ParamConverter(ProductOfferUid::class)]        $offer = null,
        #[ParamConverter(ProductVariationUid::class)]    $variation = null,
        #[ParamConverter(ProductModificationUid::class)] $modification = null,
    ): Response {
        // return $this->ErrorResponse();

        if (
            (!empty($modification) && (empty($offer) || empty($variation)))
            || (!empty($variation) && empty($offer))
        ) {
            return $this->ErrorResponse($translator);
        }

        /** Получаем событие продукта по переданным параметрам */
        $Event = $productEvent->getOneOrNullProductEvent($product, $offer, $variation, $modification);

        if (!$Event)
        {
            return $this->ErrorResponse($translator);
        }

        $cache = new ApcuAdapter();
        $key = md5($request->getClientIp().$request->headers->get('USER-AGENT'));
        $expires = 60 * 60; // Время кешировния 60 * 60 = 1 час

        if ($this->getUser())
        {
            $expires = 60 * 60 * 24; // Время кешировния 60 * 60 * 24 = 24 часа
        }

        // Получаем кеш
        if ($cache->hasItem($key))
        {
            $this->products = $cache->getItem($key)->get();
        }

        if ($this->products === null)
        {
            $this->products = new ArrayCollection();
        }

        /** @var OrderProductDTO $element */
        $predicat = function ($key, OrderProductDTO $element) use ($product, $offer, $variation, $modification) {
            return
                $element->getProduct()->equals($product)
                && (!$offer || $element->getOffer()?->equals($offer))
                && (!$variation || $element->getVariation()?->equals($variation))
                && (!$modification || $element->getModification()?->equals($modification));
        };

        $removeElement = $this->products->findFirst($predicat);

        if ($removeElement)
        {
            // Удаляем из кеша
            $cache->delete($key);

            /** получаем кеш */
            $result = $cache->get($key, function (ItemInterface $item) use ($removeElement, $expires) {
                $item->expiresAfter($expires);
                $this->products->removeElement($removeElement);

                return $this->products;
            });

            if ($result->isEmpty())
            {
                $this->addFlash($Event->getOption(), 'user.basket.success.delete', 'user.order');

                return $this->redirectToRoute('Orders:user.basket', status: 200);
            }

            return new JsonResponse(
                [
                    'type' => 'success',
                    'header' => $Event->getOption(),
                    'message' => $translator->trans('user.basket.success.delete', domain: 'user.order'),
                    'status' => 200,
                ],
                200
            );
        }

        return $this->ErrorResponse($translator);
    }

    public function ErrorResponse($translator): JsonResponse
    {
        return new JsonResponse(
            [
                'type' => 'danger',
                'header' => $translator->trans('user.page.index', domain: 'user.order'),
                'message' => $translator->trans('user.basket.danger.delete', domain: 'user.order'),
                'status' => 400,
            ],
            400
        );
    }
}
