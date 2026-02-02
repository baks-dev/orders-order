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

declare(strict_types=1);

namespace BaksDev\Orders\Order\UseCase\Admin\Return\Products;

use BaksDev\Orders\Order\Entity\Products\OrderProductInterface;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Orders\Order\UseCase\Admin\Return\Products\Items\DeletedItem\DeletedItemDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\Products\Items\OrderProductItemDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\Products\Posting\OrderProductPostingDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\Products\Price\ReturnOrderPriceDTO;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderProduct */
final class ReturnOrderProductDTO implements OrderProductInterface
{
    /** Идентификатор продукта в заказе */
    #[Assert\Uuid]
    private OrderProductUid $id;

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
    private ReturnOrderPriceDTO $price;


    public function __construct()
    {
        $this->price = new ReturnOrderPriceDTO();
    }

    public function getOrderProductId(): OrderProductUid
    {
        return $this->id;
    }

    public function setId(OrderProductUid $id): void
    {
        $this->id = $id;
    }

    /** Событие продукта */
    public function getProduct(): ProductEventUid
    {
        return $this->product;
    }

    public function setProduct(ProductEventUid|string $product): self
    {
        if(true === is_string($product))
        {
            if(empty($product))
            {
                throw new InvalidArgumentException('InvalidArgumentException ProductEvent');
            }

            $product = new ProductEventUid($product);
        }

        $this->product = $product;

        return $this;
    }

    /** Торговое предложение */
    public function getOffer(): ?ProductOfferUid
    {
        return $this->offer;
    }

    public function setOffer(ProductOfferUid|string|null $offer): self
    {
        if(true === is_string($offer))
        {
            $offer = empty($offer) ? null : new ProductOfferUid($offer);
        }

        $this->offer = $offer;

        return $this;
    }

    /** Множественный вариант торгового предложения */
    public function getVariation(): ?ProductVariationUid
    {
        return $this->variation;
    }

    public function setVariation(ProductVariationUid|string|null $variation): self
    {
        if(true === is_string($variation))
        {
            $variation = empty($variation) ? null : new ProductVariationUid($variation);
        }

        $this->variation = $variation;

        return $this;
    }

    /** Модификация множественного варианта торгового предложения  */
    public function getModification(): ?ProductModificationUid
    {
        return $this->modification;
    }

    public function setModification(ProductModificationUid|string|null $modification): self
    {
        if(true === is_string($modification))
        {
            $modification = empty($modification) ? null : new ProductModificationUid($modification);
        }

        $this->modification = $modification;

        return $this;
    }

    /** Стоимость и количество */
    public function getPrice(): ReturnOrderPriceDTO
    {
        return $this->price;
    }

    public function setPrice(ReturnOrderPriceDTO $price): self
    {
        $this->price = $price;

        return $this;
    }

}