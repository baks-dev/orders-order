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

namespace BaksDev\Orders\Order\Entity\Items\Access;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Orders\Order\Entity\Items\OrderProductItem;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Флаг для производства - Количество готовых к упаковке товаров
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders_product_item_access')]
class OrderProductItemAccess extends EntityEvent
{
    /**
     * ID
     */
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: OrderProductItem::class, inversedBy: 'access')]
    #[ORM\JoinColumn(name: 'item', referencedColumnName: 'id')]
    private OrderProductItem $item;

    /**
     * Значение свойства
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $value = false;

    public function __construct(OrderProductItem $item)
    {
        $this->item = $item;
    }

    public function __toString(): string
    {
        return (string) $this->item;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OrderProductItemAccessInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderProductItemAccessInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
