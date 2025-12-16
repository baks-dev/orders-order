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
 *
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Entity\Items;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Orders\Order\Entity\Items\Access\OrderProductItemAccess;
use BaksDev\Orders\Order\Entity\Items\Posting\OrderProductItemPosting;
use BaksDev\Orders\Order\Entity\Items\Price\OrderProductItemPrice;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Items\Const\OrderProductItemConst;
use BaksDev\Orders\Order\Type\Items\OrderProductItemUid;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Единица продукта в заказе
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders_product_item')]
#[ORM\Index(columns: ['const'])]
class OrderProductItem extends EntityEvent
{
    /**
     * ID единицы продукта в заказе
     */
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OrderProductItemUid::TYPE)]
    private OrderProductItemUid $id;

    /**
     * Связь с продуктом в заказе
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\ManyToOne(targetEntity: OrderProduct::class, inversedBy: 'item')]
    #[ORM\JoinColumn(name: 'product', referencedColumnName: 'id')]
    private OrderProduct $product;

    /**
     * Постоянный уникальный идентификатор
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: OrderProductItemConst::TYPE)]
    private readonly OrderProductItemConst $const;

    /**
     * Цена единицы продукта
     */
    #[ORM\OneToOne(targetEntity: OrderProductItemPrice::class, mappedBy: 'item', cascade: ['all'], fetch: 'EAGER')]
    private OrderProductItemPrice $price;

    /**
     * Номер разделенного отправления
     */
    #[ORM\OneToOne(targetEntity: OrderProductItemPosting::class, mappedBy: 'item', cascade: ['all'], fetch: 'EAGER')]
    private OrderProductItemPosting $posting;

    /**
     * Флаг для производства
     */
    #[ORM\OneToOne(targetEntity: OrderProductItemAccess::class, mappedBy: 'item', cascade: ['all'], fetch: 'EAGER')]
    private OrderProductItemAccess $access;

    public function __construct(OrderProduct $product)
    {
        $this->product = $product;
    }

    public function __clone(): void
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OrderProductItemInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderProductItemInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}