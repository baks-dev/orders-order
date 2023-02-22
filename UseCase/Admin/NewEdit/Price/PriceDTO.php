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

namespace BaksDev\Orders\Order\UseCase\Admin\NewEdit\Price;

use BaksDev\Orders\Order\Entity\Price\PriceInterface;

use App\System\Type\Currency\Currency;
use App\System\Type\Money\Money;
use Symfony\Component\Validator\Constraints as Assert;

final class PriceDTO implements PriceInterface
{
    /** Стоимость */
    private ?Money $price;
    
    /** Валюта */
    private Currency $currency;
    
    /** Количество в заказе */
    #[Assert\Range(min: 1)]
    private int $total = 0;
    
    
    public function __construct() {
        $this->currency = new Currency();
    }
    

    public function getPrice() : ?Money
    {
        return $this->price;
    }
    

    public function setPrice(Money|float $price) : void
    {
        $this->price = $price instanceof Money ? $price : new Money($price);
    }
    
    /**
     * @return Currency
     */
    public function getCurrency() : Currency
    {
        return $this->currency;
    }
    
    /**
     * @param string $currency
     */
    public function setCurrency(string $currency) : void
    {
        $this->currency = new Currency($currency);
    }
    
    /* TOTAL */
    
    /**
     * @return int
     */
    public function getTotal() : int
    {
        return $this->total;
    }
    
    /**
     * @param int $total
     */
    public function setTotal(int $total) : void
    {
        $this->total = $total;
    }
    
}