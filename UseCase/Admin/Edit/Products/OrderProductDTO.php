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

declare(strict_types=1);

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\Products;

use BaksDev\Orders\Order\Entity\Products\OrderProductInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderProductDTO implements OrderProductInterface
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

    /** Модификация множественного враинта торгового предложения  */
    #[Assert\Uuid]
    private ?ProductModificationUid $modification = null;

    /** Стоимость и количество */
    #[Assert\Valid]
    private Price\OrderPriceDTO $price;


    /** Карточка товара */
    private array|null $card = null;


    public function __construct()
    {
        $this->price = new Price\OrderPriceDTO();
    }


    /** Событие продукта */

    public function getProduct(): ProductEventUid
    {
        return $this->product;
    }


    public function setProduct(ProductEventUid|string $product): void
    {
        if(true === is_string($product))
        {
            $product = empty($product) ? null : new ProductEventUid($product);
        }

        $this->product = $product;
    }


    /** Торговое предложение */

    public function getOffer(): ?ProductOfferUid
    {
        return $this->offer;
    }


    public function setOffer(ProductOfferUid|string|null $offer): void
    {
        if(true === is_string($offer))
        {
            $offer = empty($offer) ? null : new ProductOfferUid($offer);
        }

        $this->offer = $offer;
    }


    /** Множественный вариант торгового предложения */

    public function getVariation(): ?ProductVariationUid
    {
        return $this->variation;
    }


    public function setVariation(ProductVariationUid|string|null $variation): void
    {
        if(true === is_string($variation))
        {
            $variation = empty($variation) ? null : new ProductVariationUid($variation);
        }

        $this->variation = $variation;
    }


    /** Модификация множественного враинта торгового предложения  */

    public function getModification(): ?ProductModificationUid
    {
        return $this->modification;
    }


    public function setModification(ProductModificationUid|string|null $modification): void
    {
        if(true === is_string($modification))
        {
            $modification = empty($modification) ? null : new ProductModificationUid($modification);
        }

        $this->modification = $modification;
    }


    /** Стоимость и количество */

    public function getPrice(): Price\OrderPriceDTO
    {
        return $this->price;
    }

    public function setPrice(Price\OrderPriceDTO $price): void
    {
        $this->price = $price;
    }


    /** Карточка товара */

    public function getCard(): array
    {
        return $this->card;
    }

    public function setCard(bool|array $card): void
    {
        $this->card = $card ?: [];
    }

}
