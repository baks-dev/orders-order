<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Admin\New\preProduct;

use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;

/** @see OrderProduct */
final class PreProductDTO
{
    /** Категория */
    private ?CategoryProductUid $category = null;

    /** Продукт */
    private ?ProductEventUid $preProduct = null;

    /** Торговое предложение */
    private ?ProductOfferUid $preOffer = null;

    /** Множественный вариант */
    private ?ProductVariationUid $preVariation = null;

    /** Модификация множественного варианта */
    private ?ProductModificationUid $preModification = null;

    /** Количество */
    private ?int $preTotal = null;

    /** Персональная скидка пользователя */
    private ?int $discount = null;

    /**
     * Category
     */
    public function getCategory(): ?CategoryProductUid
    {
        return $this->category;
    }

    public function setCategory(?CategoryProductUid $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * PreProduct
     */
    public function getPreProduct(): ?ProductEventUid
    {
        return $this->preProduct;
    }

    public function setPreProduct(?ProductEventUid $preProduct): self
    {
        $this->preProduct = $preProduct;
        return $this;
    }

    /**
     * PreOffer
     */
    public function getPreOffer(): ?ProductOfferUid
    {
        return $this->preOffer;
    }

    public function setPreOffer(?ProductOfferUid $preOffer): self
    {
        $this->preOffer = $preOffer;
        return $this;
    }

    /**
     * PreVariation
     */
    public function getPreVariation(): ?ProductVariationUid
    {
        return $this->preVariation;
    }

    public function setPreVariation(?ProductVariationUid $preVariation): self
    {
        $this->preVariation = $preVariation;
        return $this;
    }

    /**
     * PreModification
     */
    public function getPreModification(): ?ProductModificationUid
    {
        return $this->preModification;
    }

    public function setPreModification(?ProductModificationUid $preModification): self
    {
        $this->preModification = $preModification;
        return $this;
    }

    /**
     * PreTotal
     */
    public function getPreTotal(): ?int
    {
        return $this->preTotal;
    }

    public function setPreTotal(?int $preTotal): self
    {
        $this->preTotal = $preTotal;
        return $this;
    }

    /**
     * Discount
     */
    public function setDiscount(?int $discount): PreProductDTO
    {
        $this->discount = $discount;
        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }
}