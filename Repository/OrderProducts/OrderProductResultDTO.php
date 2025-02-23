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

namespace BaksDev\Orders\Order\Repository\OrderProducts;

use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;

final readonly class OrderProductResultDTO
{
    public function __construct(
        private string $order_id,
        private string $order_event,
        private string $product_id,
        private string $product_event,
        private ?string $product_offer,
        private ?string $product_offer_const,
        private ?string $product_offer_value,
        private ?string $product_variation,
        private ?string $product_variation_const,
        private ?string $product_variation_value,
        private ?string $product_modification,
        private ?string $product_modification_const,
        private ?string $product_modification_value,
    ) {}

    /**
     * OderId
     */
    public function getOderId(): OrderUid
    {
        return new OrderUid($this->order_id);
    }

    /**
     * OderEvent
     */
    public function getOderEvent(): OrderEventUid
    {
        return new OrderEventUid($this->order_event);
    }

    /**
     * ProductId
     */
    public function getProduct(): ProductUid
    {
        return new ProductUid($this->product_id);
    }

    /**
     * ProductEvent
     */
    public function getProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->product_event);
    }

    /**
     * ProductOffer
     */

    public function getProductOffer(): ProductOfferUid|false
    {
        return $this->product_offer ? new ProductOfferUid($this->product_offer) : false;
    }


    public function getProductOfferConst(): ProductOfferConst|false
    {
        return $this->product_offer_const ? new ProductOfferConst($this->product_offer_const) : false;
    }


    public function getProductOfferValue(): ?string
    {
        return $this->product_offer_value;
    }

    /**
     * ProductVariation
     */

    public function getProductVariation(): ProductVariationUid|false
    {
        return $this->product_variation ? new ProductVariationUid($this->product_variation) : false;
    }

    public function getProductVariationConst(): ProductVariationConst|false
    {
        return $this->product_variation_const ? new ProductVariationConst($this->product_variation_const) : false;
    }

    public function getProductVariationValue(): ?string
    {
        return $this->product_variation_value;
    }

    /**
     * ProductModification
     */

    public function getProductModification(): ProductModificationUid|false
    {
        return $this->product_modification ? new ProductModificationUid($this->product_modification) : false;
    }

    public function getProductModificationConst(): ProductModificationConst|false
    {
        return $this->product_modification_const ? new ProductModificationConst($this->product_modification_const) : false;
    }

    public function getProductModificationValue(): ?string
    {
        return $this->product_modification_value;
    }

}