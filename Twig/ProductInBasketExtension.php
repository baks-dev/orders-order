<?php
/*
 * Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Twig;

use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\OrderProductDTO;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ProductInBasketExtension extends AbstractExtension
{
    private AppCacheInterface $cache;

    private RequestStack $requestStack;

    public function __construct(AppCacheInterface $cache, RequestStack $requestStack)
    {
        $this->cache = $cache;
        $this->requestStack = $requestStack;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('product_in_basket', [$this, 'call']),
        ];
    }

    public function call(
        ProductEventUid $event,
        ?ProductOfferUid $offer,
        ?ProductVariationUid $variation,
        ?ProductModificationUid $modification
    ): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        $key = md5($request->getClientIp().$request->headers->get('USER-AGENT'));
        $cache = $this->cache->init('orders-order-basket');

        if(null === $cache || false === $cache->hasItem($key))
        {
            return false;
        }

        $products = ($cache->getItem($key))->get();

        /**
         * @var ArrayCollection $products
         * Проверяем, есть ли в корзине данный товар
         */
        return $products->exists(function ($key, OrderProductDTO $product) use (
            $event,
            $offer,
            $variation,
            $modification
        ) {
            return $product->getProduct()->equals($event)
                && ((is_null($product->getOffer()) === true && is_null($offer) === true)
                    || $product->getOffer()?->equals($offer)
                )
                && ((is_null($product->getVariation()) === true && is_null($variation)=== true)
                    || $product->getVariation()?->equals($variation)
                )
                && ((is_null($product->getModification()) === true && is_null($modification) === true)
                    || $product->getModification()?->equals($modification)
                );
        });
    }
}