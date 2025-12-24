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

namespace BaksDev\Orders\Order\Entity\Items\Price;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Orders\Order\Entity\Items\OrderProductItem;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Цена единицы продукта
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders_product_item_price')]
class OrderProductItemPrice extends EntityEvent
{
    /**
     * ID
     */
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: OrderProductItem::class, inversedBy: 'price')]
    #[ORM\JoinColumn(name: 'item', referencedColumnName: 'id')]
    private OrderProductItem $item;

    /**
     * Стоимость
     */
    #[ORM\Column(type: Money::TYPE)]
    private Money $price;

    /**
     * Валюта
     */
    #[ORM\Column(type: Currency::TYPE, length: 3)]
    private Currency $currency;

    public function __construct(OrderProductItem $item)
    {
        $this->item = $item;
        $this->currency = new Currency();
    }

    public function __toString(): string
    {
        return (string) $this->item;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OrderProductItemPriceInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderProductItemPriceInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}