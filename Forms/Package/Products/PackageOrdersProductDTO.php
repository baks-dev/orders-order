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

namespace BaksDev\Orders\Order\Forms\Package\Products;

use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\OrderProductInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;

/** @see OrderProduct */
final class PackageOrdersProductDTO implements OrderProductInterface
{
    private ProductEventUid $product;

    private ?ProductOfferUid $offer = null;

    private ?ProductVariationUid $variation = null;

    private ?ProductModificationUid $modification = null;

    /** Карточка товара */
    private ProductUserBasketResult|false $card = false;

    private int $total = 1;

    private int $stock = 0;

    /** Количество в заказах */
    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getProduct(): ProductEventUid
    {
        return $this->product;
    }

    public function setProduct(ProductEventUid $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getOffer(): ?ProductOfferUid
    {
        return $this->offer;
    }

    public function setOffer(?ProductOfferUid $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    public function getVariation(): ?ProductVariationUid
    {
        return $this->variation;
    }

    public function setVariation(?ProductVariationUid $variation): self
    {
        $this->variation = $variation;
        return $this;
    }

    public function getModification(): ?ProductModificationUid
    {
        return $this->modification;
    }

    public function setModification(?ProductModificationUid $modification): self
    {
        $this->modification = $modification;
        return $this;
    }

    /** Карточка товара */
    public function getCard(): ProductUserBasketResult|false
    {
        return $this->card;
    }

    public function setCard(ProductUserBasketResult $card): self
    {
        $this->card = $card;
        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }
}
