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

namespace BaksDev\Orders\Order\UseCase\Admin\NewEdit;

use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderDTO implements OrderEventInterface
{
    /** Идентификатор события */
    #[Assert\Uuid]
    private ?OrderEventUid $id = null;

    /** Статус заказа */
    private OrderStatus $status;

    /** Коллекция продукции в заказе */
    #[Assert\Valid]
    private ArrayCollection $product;

    /** Пользователь */
    #[Assert\Valid]
    private ?User\OrderUserDTO $users;




    public function __construct()
    {
        $this->product = new ArrayCollection();
        $this->users = new User\OrderUserDTO();
    }


    public function getEvent(): ?OrderEventUid
    {
        return $this->id;
    }

    public function resetId()
    {
        $this->id = null;
    }


    /**
     * Коллекция продукции в заказе
     */

    public function getProduct(): ArrayCollection
    {
        return $this->product;
    }


    public function setProduct(ArrayCollection $product): void
    {
        $this->product = $product;
    }

    public function addProduct(Products\OrderProductDTO $product): void
    {
        if(!$this->product->contains($product))
        {
            $this->product->add($product);
        }
    }


    public function removeProduct(Products\OrderProductDTO $product): void
    {
        $this->product->removeElement($product);
    }


    /**
     * Пользователь
     */

    public function getUsers(): ?User\OrderUserDTO
    {
        return $this->users;
    }


    public function setUsers(?User\OrderUserDTO $users): void
    {
        $this->users = $users;
    }


    /**
     * Статус заказа
     */

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }


    public function setStatus(OrderStatus $status): void
    {
        $this->status = $status;
    }
}