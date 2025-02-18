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

namespace BaksDev\Orders\Order\Repository\RelevantNewOrderByProduct;

use BaksDev\Delivery\Entity\Delivery;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;

interface RelevantNewOrderByProductInterface
{
    public function forDelivery(Delivery|DeliveryUid|string $delivery): self;

    public function forProductEvent(ProductEvent|ProductEventUid|string $product): self;

    public function forOffer(ProductOffer|ProductOfferUid|string $offer): self;

    public function forVariation(ProductVariation|ProductVariationUid|string|null|false $variation): self;

    public function forModification(ProductModification|ProductModificationUid|string|null|false $modification): self;

    public function onlyNewStatus(): self;

    public function onlyPackageStatus(): self;


    /** Только заказы, которые требуют производства */
    public function filterProductAccess(): self;

    /** Только заказы, которые произведены и готовы к упаковке */
    public function filterProductNotAccess(): self;


    /**
     * Метод возвращает событие самого старого заказа (более актуального для сборки)
     * на указанный способ доставки и в котором имеется указанная продукция
     */
    public function find(): OrderEvent|false;

    /**
     * Метод возвращает все заказы самого старого (более актуального) нового заказа
     * на указанный способ доставки и в котором имеется указанная продукция
     */
    public function findAll(): array|false;

}