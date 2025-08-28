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

namespace BaksDev\Orders\Order\Repository\ProductUserBasket;

use BaksDev\Products\Product\Repository\ProductPriceResultInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use DateMalformedStringException;
use DateTimeImmutable;
use JsonException;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/** @see ProductUserBasketRepository */
#[Exclude]
final  class ProductUserBasketResult implements ProductPriceResultInterface
{
    private int|null $stock = 0;

    public function __construct(
        private readonly string $id,
        private readonly string $event,

        private readonly string $product_active_from,
        private readonly string $current_event,
        private readonly string $product_name,
        private readonly string $product_article,
        private readonly string $product_url,

        private readonly ?string $product_offer_uid,
        private readonly ?string $product_offer_const,
        private readonly ?string $product_offer_value,
        private readonly ?string $product_offer_postfix,
        private readonly ?string $product_offer_reference,
        private readonly ?string $product_offer_name,

        private readonly ?string $product_variation_uid,
        private readonly ?string $product_variation_const,
        private readonly ?string $product_variation_value,
        private readonly ?string $product_variation_postfix,
        private readonly ?string $product_variation_reference,
        private readonly ?string $product_variation_name,

        private readonly ?string $product_modification_uid,
        private readonly ?string $product_modification_const,
        private readonly ?string $product_modification_value,
        private readonly ?string $product_modification_postfix,
        private readonly ?string $product_modification_reference,
        private readonly ?string $product_modification_name,

        private readonly ?int $product_price,
        private readonly ?int $product_old_price,
        private readonly ?string $product_currency,
        private readonly int $product_quantity,

        private readonly ?string $product_image,
        private readonly ?string $product_image_ext,
        private readonly ?bool $product_image_cdn,

        private readonly string $category_name,
        private readonly string $category_url,
        private readonly int $category_minimal,
        private readonly int $category_input,
        private readonly int $category_threshold,

        private readonly string $category_section_field,

        private readonly string|null $product_invariable_id,

        private ?bool $promotion_active = null,
        private string|int|null $promotion_price = null,

        private readonly string|null $profile_discount = null,
        private readonly string|null $project_discount = null,
    ) {}

    /**
     * Main
     */
    public function getProductId(): ProductUid
    {
        return new ProductUid($this->id);
    }

    /**
     * Event
     */
    public function getProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->event);
    }

    /**
     * CurrentEvent
     */
    public function getCurrentProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->current_event);
    }

    /**
     * ProductActiveFrom
     *
     * @throws DateMalformedStringException
     */
    public function getProductActiveFrom(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->product_active_from);
    }


    /**
     * ProductName
     */
    public function getProductName(): string
    {
        return $this->product_name;
    }

    /**
     * ProductArticle
     */
    public function getProductArticle(): string
    {
        return $this->product_article;
    }

    /**
     * ProductUrl
     */
    public function getProductUrl(): string
    {
        return $this->product_url;
    }


    /**
     * ProductOfferName
     */
    public function getProductOfferName(): ?string
    {
        return $this->product_offer_name;
    }

    /**
     * ProductOfferUid
     */
    public function getProductOfferUid(): ?ProductOfferUid
    {
        return $this->product_offer_uid ? new ProductOfferUid($this->product_offer_uid) : null;
    }

    /**
     * ProductOfferConst
     */
    public function getProductOfferConst(): ?ProductOfferConst
    {
        return $this->product_offer_const ? new ProductOfferConst($this->product_offer_const) : null;
    }

    /**
     * ProductOfferValue
     */
    public function getProductOfferValue(): ?string
    {
        return $this->product_offer_value;
    }

    /**
     * ProductOfferPostfix
     */
    public function getProductOfferPostfix(): ?string
    {
        return $this->product_offer_postfix;
    }

    /**
     * ProductOfferReference
     */
    public function getProductOfferReference(): ?string
    {
        return $this->product_offer_reference;
    }

    /**
     * ProductVariation
     */

    public function getProductVariationName(): ?string
    {
        return $this->product_variation_name;
    }

    public function getProductVariationUid(): ?ProductVariationUid
    {
        return $this->product_variation_uid ? new ProductVariationUid($this->product_variation_uid) : null;
    }

    public function getProductVariationConst(): ?ProductVariationConst
    {
        return $this->product_variation_const ? new ProductVariationConst($this->product_variation_const) : null;
    }

    public function getProductVariationValue(): ?string
    {
        return $this->product_variation_value;
    }

    public function getProductVariationPostfix(): ?string
    {
        return $this->product_variation_postfix;
    }

    public function getProductVariationReference(): ?string
    {
        return $this->product_variation_reference;
    }


    /**
     * ProductModification
     */

    public function getProductModificationName(): ?string
    {
        return $this->product_modification_name;
    }

    public function getProductModificationUid(): ?ProductModificationUid
    {
        return $this->product_modification_uid ? new ProductModificationUid($this->product_modification_uid) : null;
    }

    public function getProductModificationConst(): ?ProductModificationConst
    {
        return $this->product_modification_const ? new ProductModificationConst($this->product_modification_const) : null;
    }

    public function getProductModificationValue(): ?string
    {
        return $this->product_modification_value;
    }

    public function getProductModificationPostfix(): ?string
    {
        return $this->product_modification_postfix;
    }

    public function getProductModificationReference(): ?string
    {
        return $this->product_modification_reference;
    }

    /**
     * ProductImage
     */

    public function getProductImage(): ?string
    {
        return $this->product_image;
    }

    public function getProductImageExt(): ?string
    {
        return $this->product_image_ext;
    }

    public function getProductImageCdn(): bool
    {
        return $this->product_image_cdn === true;
    }

    /**
     * ProductPrice
     */
    public function getProductPrice(): Money|false
    {
        if(empty($this->product_price))
        {
            return new Money(0);
        }

        /** Оригинальная цена */
        $price = new Money($this->product_price, true);

        /** Кастомная цена */
        if(false === empty($this->promotion_price) && true === $this->promotion_active)
        {
            $price->applyString($this->promotion_price);
        }

        /** Скидка магазина */
        if(false === empty($this->project_discount))
        {
            $price->applyString($this->project_discount);
        }

        /** Скидка пользователя */
        if(false === empty($this->profile_discount))
        {
            $price->applyString($this->profile_discount);
        }

        return $price;
    }

    public function getProductOldPrice(): Money|false
    {
        if(empty($this->product_old_price))
        {
            return false;
        }

        $price = new Money($this->product_old_price, true);

        /** Кастомная цена */
        if(false === empty($this->promotion_price) && true === $this->promotion_active)
        {
            $price->applyString($this->promotion_price);
        }

        /** Скидка магазина */
        if(false === empty($this->project_discount))
        {
            $price->applyString($this->project_discount);
        }

        /** Скидка пользователя */
        if(false === empty($this->profile_discount))
        {
            $price->applyString($this->profile_discount);
        }

        return $price;
    }


    public function getProductCurrency(): Currency
    {
        return new Currency($this->product_currency);
    }

    public function getProductQuantity(): int
    {
        return $this->product_quantity;
    }

    /**
     * Category
     */

    public function getCategoryName(): string
    {
        return $this->category_name;
    }

    /**
     * CategoryUrl
     */
    public function getCategoryUrl(): string
    {
        return $this->category_url;
    }

    /**
     * CategoryMinimal
     */
    public function getCategoryMinimal(): int
    {
        return $this->category_minimal;
    }

    /**
     * CategoryInput
     */
    public function getCategoryInput(): int
    {
        return $this->category_input;
    }

    /**
     * CategoryThreshold
     */
    public function getCategoryThreshold(): int
    {
        return $this->category_threshold;
    }

    /**
     * CategorySectionField
     *
     * @throws JsonException
     */
    public function getCategorySectionField(): array
    {
        return json_decode($this->category_section_field, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getProductInvariableId(): ?ProductInvariableUid
    {
        if(is_null($this->product_invariable_id))
        {
            return null;
        }

        return new ProductInvariableUid($this->product_invariable_id);
    }

    public function getProfileDiscount(): ?int
    {
        return $this->profile_discount;
    }

    /** Возвращает разницу между старой и новой ценами в процентах */
    public function getDiscountPercent(): int|null
    {
        if(false === $this->getProductPrice())
        {
            return null;
        }

        if(false === $this->getProductOldPrice())
        {
            return null;
        }

        $price = $this->getProductPrice()->getValue();
        $oldPrice = $this->getProductOldPrice()->getValue();

        $discountPercent = null;

        if($oldPrice > $price)
        {
            $discountPercent = (int) (($oldPrice - $price) / $oldPrice * 100);
        }

        return $discountPercent;
    }

    public function getStock(): ?int
    {
        return $this->stock ? max($this->stock, 0) : 0;
    }

    public function setStock(?int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }
}