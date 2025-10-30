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

namespace BaksDev\Orders\Order\Entity\Products\Price;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

// Стоимость Продукта

#[ORM\Entity]
#[ORM\Table(name: 'orders_price')]
class OrderPrice extends EntityEvent
{
    /** ID события */
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: OrderProduct::class, inversedBy: 'price')]
    #[ORM\JoinColumn(name: 'product', referencedColumnName: 'id')]
    private OrderProduct $product;

    /** Стоимость */
    #[ORM\Column(type: Money::TYPE)]
    private Money $price;

    /** Валюта */
    #[ORM\Column(type: Currency::TYPE, length: 3)]
    private Currency $currency;

    /** Количество в заказе */
    #[ORM\Column(type: Types::INTEGER)]
    private int $total = 0;

    /** Количество готовых к упаковке товаров  */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $access = 0;

    public function __construct(OrderProduct $product)
    {
        $this->product = $product;
        $this->currency = new Currency();
    }

    public function __toString(): string
    {
        return (string) $this->product;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OrderPriceInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderPriceInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }
}
