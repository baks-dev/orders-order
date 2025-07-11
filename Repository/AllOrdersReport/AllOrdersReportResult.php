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

namespace BaksDev\Orders\Order\Repository\AllOrdersReport;

use BaksDev\Reference\Money\Type\Money;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class AllOrdersReportResult
{
    public function __construct(

        private readonly string $number,
        private readonly string $mod_date,

        private readonly int $order_price,
        private readonly int $total,
        private readonly int $money,
        private readonly ?int $profit,

        private readonly ?string $product_name,
        private readonly ?string $product_article,
        private readonly ?int $product_price,

        private readonly ?string $product_offer_value,
        private readonly ?string $product_offer_reference,
        private readonly ?string $product_offer_postfix,

        private readonly ?string $product_variation_value,
        private readonly ?string $product_variation_reference,
        private readonly ?string $product_variation_postfix,

        private readonly ?string $product_modification_value,
        private readonly ?string $product_modification_reference,
        private readonly ?string $product_modification_postfix,

        private readonly ?string $delivery_name,
        private readonly ?int $delivery_price,

        private ?string $profile_discount = null,
        private ?string $project_discount = null

    ) {}

    public function getDate(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->mod_date);
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getProductPrice(): Money
    {
        $price = new Money($this->product_price, true);

        /**
         * Применяем настройки цены профиля проекта к стоимости товара
         */
        if(false === empty($this->project_discount))
        {
            $price->applyString($this->project_discount);
        }

        /**
         * Применяем настройки цены профиля пользователя к стоимости товара
         */
        if(false === empty($this->profile_discount))
        {
            $price->applyString($this->profile_discount);
        }

        return $price;
    }

    public function getOrderPrice(): Money
    {
        return new Money($this->order_price, true);
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getMoney(): Money
    {
        return new Money($this->money, true);
    }

    public function getProfit(): Money
    {
        return new Money($this->profit, true);
    }

    public function getProductName(): ?string
    {
        return $this->product_name;
    }

    public function getProductArticle(): ?string
    {
        return $this->product_article;
    }

    /** Offer */

    public function getProductOfferValue(): ?string
    {
        return $this->product_offer_value;
    }

    public function getProductOfferPostfix(): ?string
    {
        return $this->product_offer_postfix;
    }

    public function getProductOfferReference(): ?string
    {
        return $this->product_offer_reference;
    }


    /** Variation */

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


    /** Modification */

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


    public function getDeliveryName(): ?string
    {
        return $this->delivery_name;
    }

    public function getDeliveryPrice(): Money
    {
        return new Money($this->delivery_price, true);
    }

}