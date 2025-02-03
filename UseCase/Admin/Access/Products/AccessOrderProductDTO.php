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

namespace BaksDev\Orders\Order\UseCase\Admin\Access\Products;

use BaksDev\Orders\Order\Entity\Products\OrderProductInterface;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderProduct */
final class AccessOrderProductDTO implements OrderProductInterface
{
    /** Идентификатор продукта в заказе */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly OrderProductUid $id;

    /** Событие продукта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly ProductEventUid $product;

    /** Торговое предложение */
    #[Assert\Uuid]
    private readonly ?ProductOfferUid $offer;

    /** Множественный вариант торгового предложения */
    #[Assert\Uuid]
    private readonly ?ProductVariationUid $variation;

    /** Модификация множественного враинта торгового предложения  */
    #[Assert\Uuid]
    private readonly ?ProductModificationUid $modification;

    /** Стоимость и количество */
    #[Assert\Valid]
    private Price\AccessOrderPriceDTO $price;

    public function __construct()
    {
        $this->price = new Price\AccessOrderPriceDTO();
    }

    /**
     * Id
     */
    public function getId(): OrderProductUid
    {
        return $this->id;
    }


    /** Событие продукта */

    public function getProduct(): ProductEventUid
    {
        return $this->product;
    }

    /** Торговое предложение */

    public function getOffer(): ?ProductOfferUid
    {
        return $this->offer;
    }

    /** Множественный вариант торгового предложения */

    public function getVariation(): ?ProductVariationUid
    {
        return $this->variation;
    }

    /** Модификация множественного вараинта торгового предложения  */

    public function getModification(): ?ProductModificationUid
    {
        return $this->modification;
    }

    /** Стоимость и количество */

    public function getPrice(): Price\AccessOrderPriceDTO
    {
        return $this->price;
    }

}
