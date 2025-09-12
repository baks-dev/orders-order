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

namespace BaksDev\Orders\Order\UseCase\Admin\Package\Products;

use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\OrderProductInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderProduct */
final class PackageOrderProductDTO implements OrderProductInterface
{

    /** Событие продукта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ProductEventUid $product;

    /** Торговое предложение */
    #[Assert\Uuid]
    private ?ProductOfferUid $offer = null;

    /** Множественный вариант торгового предложения */
    #[Assert\Uuid]
    private ?ProductVariationUid $variation = null;

    /** Модификация множественного варианта торгового предложения  */
    #[Assert\Uuid]
    private ?ProductModificationUid $modification = null;

    /** Стоимость и количество */
    #[Assert\Valid]
    private Price\PackageOrderPriceDTO $price;

    /** Коллекция перемещения  */
    #[Assert\Valid]
    private ?Moving\MovingProductStockDTO $move = null;


    /** Карточка товара */
    private ProductUserBasketResult|false $card = false;


    /** Событие продукта */
    public function getProduct(): ProductEventUid
    {
        return $this->product;
    }

    public function setProduct(ProductEventUid $product): void
    {
        $this->product = $product;
    }

    /** Торговое предложение */
    public function getOffer(): ?ProductOfferUid
    {
        return $this->offer;
    }

    public function setOffer(?ProductOfferUid $offer): void
    {
        $this->offer = $offer;
    }

    /** Множественный вариант торгового предложения */
    public function getVariation(): ?ProductVariationUid
    {
        return $this->variation;
    }

    public function setVariation(?ProductVariationUid $variation): void
    {
        $this->variation = $variation;
    }

    /** Модификация множественного враинта торгового предложения  */
    public function getModification(): ?ProductModificationUid
    {
        return $this->modification;
    }

    public function setModification(?ProductModificationUid $modification): void
    {
        $this->modification = $modification;
    }

    /** Стоимость и количество */
    public function getPrice(): Price\PackageOrderPriceDTO
    {
        return $this->price;
    }

    public function setPrice(Price\PackageOrderPriceDTO $price): void
    {
        $this->price = $price;
    }

    /** Карточка товара */
    public function getCard(): ProductUserBasketResult|false
    {
        return $this->card;
    }

    public function setCard(ProductUserBasketResult $card): void
    {
        $this->card = $card;
    }

    /** Коллекция перемещения  */
    public function getMove(): ?Moving\MovingProductStockDTO
    {
        return $this->move;
    }

    public function setMove(Moving\MovingProductStockDTO $move): void
    {
        $this->move = $move;
    }
}
