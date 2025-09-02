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

namespace BaksDev\Orders\Order\Repository\AllProductsOrdersReport;

use BaksDev\Reference\Money\Type\Money;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class AllProductsOrdersReportResult
{
    public function __construct(
        private string $product_name,
        private string $product_article,
        private ?string $total,
        private ?string $stock_total,

        private ?string $product_offer_value,
        private ?string $product_offer_reference,
        private ?string $product_offer_postfix,

        private ?string $product_variation_value,
        private ?string $product_variation_reference,
        private ?string $product_variation_postfix,

        private ?string $product_modification_value,
        private ?string $product_modification_reference,
        private ?string $product_modification_postfix,
    ) {}

    public function getProductName(): string
    {
        return $this->product_name;
    }

    public function getProductArticle(): string
    {
        return $this->product_article;
    }

    public function getTotal(): int
    {
        if(empty($this->total))
        {
            return 0;
        }

        if(false === json_validate($this->total))
        {
            return 0;
        }

        $decode = json_decode($this->total, false, 512, JSON_THROW_ON_ERROR);

        $total = 0;

        foreach($decode as $item)
        {
            if(empty($item->total))
            {
                continue;
            }

            $total += $item->total;
        }

        return $total;
    }

    public function getStockTotal(): int
    {
        if(empty($this->stock_total))
        {
            return 0;
        }

        if(false === json_validate($this->stock_total))
        {
            return 0;
        }

        $decode = json_decode($this->stock_total, false, 512, JSON_THROW_ON_ERROR);

        $quantity = 0;

        foreach($decode as $item)
        {
            $quantity += (empty($item->total) ? 0 : $item->total);
            $quantity -= (empty($item->reserve) ? 0 : $item->reserve);
        }

        return max($quantity, 0);
    }

    public function getMoney(): Money
    {
        if(empty($this->total))
        {
            return new Money(0);
        }

        if(false === json_validate($this->total))
        {
            return new Money(0);
        }

        $decode = json_decode($this->total, false, 512, JSON_THROW_ON_ERROR);

        $money = new Money(0);

        foreach($decode as $item)
        {
            if(empty($item->money))
            {
                continue;
            }

            $money->add(new Money($item->money));

            //$total += (empty($item->item) ? 0 : $item->total);
        }



        return new Money($this->money, true);
    }

    /** Offer */

    public function getProductOfferValue(): ?string
    {
        return $this->product_offer_value;
    }

    public function getProductOfferReference(): ?string
    {
        return $this->product_offer_reference;
    }

    public function getProductOfferPostfix(): ?string
    {
        return $this->product_offer_postfix;
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

}