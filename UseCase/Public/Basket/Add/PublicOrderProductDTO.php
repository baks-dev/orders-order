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

namespace BaksDev\Orders\Order\UseCase\Public\Basket\Add;

use BaksDev\Orders\Order\Entity\Products\OrderProductInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\Items\PublicOrderProductItemDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\Price\PublicOrderPriceDTO;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

final class PublicOrderProductDTO implements OrderProductInterface
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

    /** Модификация множественного вараинта торгового предложения  */
    #[Assert\Uuid]
    private ?ProductModificationUid $modification = null;

    /** Стоимость и количество */
    #[Assert\Valid]
    private PublicOrderPriceDTO $price;


    /** Карточка товара */
    private ProductUserBasketResult|array $card;

    /**
     * Коллекция единиц товара
     *
     * @var ArrayCollection<int, PublicOrderProductItemDTO> $item
     */
    #[Assert\Valid]
    private ArrayCollection|null $item = null;

    public function __construct()
    {
        $this->price = new PublicOrderPriceDTO();
        $this->item = new ArrayCollection();
    }

    //    /** Идентификтаор продукта */
    //
    //    public function getUid(): ProductUid
    //    {
    //        return $this->uid;
    //    }
    //
    //
    //    public function setUid(ProductUid $uid): void
    //    {
    //        $this->uid = $uid;
    //    }
    //

    /** Событие продукта */

    public function getProduct(): ProductEventUid
    {
        return $this->product;
    }


    public function setProduct(ProductEventUid $product): self
    {
        $this->product = $product;
        return $this;
    }


    /** Торговое предложение */

    public function getOffer(): ?ProductOfferUid
    {
        return $this->offer;
    }


    public function setOffer(ProductOfferUid|null|false $offer): self
    {
        $this->offer = $offer ?: null;
        return $this;
    }


    /** Множественный вариант торгового предложения */

    public function getVariation(): ?ProductVariationUid
    {
        return $this->variation;
    }


    public function setVariation(ProductVariationUid|null|false $variation): self
    {
        $this->variation = $variation ?: null;
        return $this;
    }


    /** Модификация множественного варианта торгового предложения  */

    public function getModification(): ?ProductModificationUid
    {
        return $this->modification;
    }


    public function setModification(ProductModificationUid|null|false $modification): self
    {
        $this->modification = $modification ?: null;
        return $this;
    }


    /** Стоимость и количество */

    public function getPrice(): PublicOrderPriceDTO
    {
        return $this->price;
    }

    public function setPrice(PublicOrderPriceDTO $price): self
    {
        $this->price = $price;
        return $this;
    }


    /** Карточка товара */

    public function getCard(): ProductUserBasketResult|array
    {
        return $this->card;
    }

    public function setCard(ProductUserBasketResult|array $card): self
    {
        $this->card = $card;
        return $this;
    }


    /**
     * Коллекция разделенных отправлений одного заказа
     *
     * @return ArrayCollection<int, PublicOrderProductItemDTO>
     */
    public function getItem(): ArrayCollection
    {
        return $this->item;
    }

    public function addItem(PublicOrderProductItemDTO $item): void
    {
        false === empty($this->item) ?: $this->item = new ArrayCollection();

        $exist = $this->item->exists(function(int $k, PublicOrderProductItemDTO $value) use ($item) {
            /** @var PublicOrderProductItemDTO $item */
            return $value->getConst()->equals($item->getConst());
        });

        if(false === $exist)
        {
            $this->item->add($item);
        }
    }

    public function removeItem(PublicOrderProductItemDTO $item): void
    {
        $this->item->removeElement($item);
    }

}
