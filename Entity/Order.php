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

namespace BaksDev\Orders\Order\Entity;

use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// Order

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    /** ID */
    #[ORM\Id]
    #[ORM\Column(type: OrderUid::TYPE)]
    private OrderUid $id;

    /** @deprecated Переносится в invariable */
    #[ORM\Column(type: Types::STRING, length: 20, unique: true, nullable: true)]
    private ?string $number = null;

    /** ID События */
    #[ORM\Column(type: OrderEventUid::TYPE, unique: true)]
    private OrderEventUid $event;

    public function __construct()
    {
        $this->id = new OrderUid();

        /** Генерируем идентификатор заказа */
        /* $this->number = number_format((microtime(true) * 100), 0, '.', '.'); */
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    /**
     * Id
     */
    public function getId(): OrderUid
    {
        return $this->id;
    }

    public function setId(OrderUid $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Number
     * @deprecated Переносится в invariable
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /** @deprecated Переносится в invariable */
    public function setNumber(string $number): self
    {
        $this->number = $number;
        return $this;
    }


    public function getEvent(): OrderEventUid
    {
        return $this->event;
    }

    public function setEvent(OrderEventUid|OrderEvent $event): self
    {
        if($event instanceof OrderEvent)
        {
            if($this->number === null && $number = $event->getOrderNumber())
            {
                $this->number = $number;
            }
        }

        if($this->number === null)
        {
            $this->number = number_format((microtime(true) * 100), 0, '.', '.');
        }

        $this->event = $event instanceof OrderEvent ? $event->getId() : $event;

        return $this;
    }
}
