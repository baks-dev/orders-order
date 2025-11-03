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

namespace BaksDev\Orders\Order\UseCase\Public\Basket;

use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\UseCase\Public\Basket\Invariable\OrderInvariableDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\Project\OrderProjectDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\Service\BasketServiceDTO;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderEvent */
final class OrderDTO implements OrderEventInterface
{
    /** Идентификатор события */
    #[Assert\Uuid]
    private ?OrderEventUid $id = null;

    /** Постоянная величина */
    #[Assert\Valid]
    private readonly OrderInvariableDTO $invariable;

    /** Коллекция продукции в заказе */
    #[Assert\Valid]
    private ArrayCollection $product;

    /** Пользователь */
    #[Assert\Valid]
    private User\OrderUserDTO $usr;

    /** Коллекция услуг в заказе */
    #[Assert\Valid]
    private ArrayCollection $serv;

    /** Комментарий к заказу */
    private ?string $comment = null;


    private OrderProjectDTO $project;

    public function __construct()
    {
        $this->product = new ArrayCollection();
        $this->serv = new ArrayCollection();
        $this->usr = new User\OrderUserDTO();
        $this->invariable = new OrderInvariableDTO();
        $this->project = new OrderProjectDTO();
    }

    public function getEvent(): ?OrderEventUid
    {
        return $this->id;
    }

    public function resetId(): void
    {
        $this->id = null;
    }

    /**
     * Invariable
     */
    public function getInvariable(): OrderInvariableDTO
    {
        return $this->invariable;
    }

    /** Коллекция продукции в заказе */
    public function getProduct(): ArrayCollection
    {
        return $this->product;
    }

    public function setProduct(ArrayCollection $product): void
    {
        $this->product = $product;
    }

    public function addProduct(Add\OrderProductDTO $product): void
    {
        if(!$this->product->contains($product))
        {
            $this->product->add($product);
        }
    }

    public function removeProduct(Add\OrderProductDTO $product): void
    {
        $this->product->removeElement($product);
    }

    /** Пользователь */
    public function getUsr(): User\OrderUserDTO
    {
        return $this->usr;
    }

    public function setUsr(User\OrderUserDTO $users): void
    {
        $this->usr = $users;
    }

    /**
     * Comment
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }


    /** Коллекция услуг в заказе */

    public function getServ(): ArrayCollection
    {
        return $this->serv;
    }

    public function setServ(ArrayCollection $serv): void
    {
        $this->serv = $serv;
    }

    public function addServ(BasketServiceDTO $serv): void
    {
        if(!$this->serv->contains($serv))
        {
            $this->serv->add($serv);
        }
    }

    public function removeServ(BasketServiceDTO $serv): void
    {
        $this->serv->removeElement($serv);
    }

    public function getProject(): OrderProjectDTO
    {
        return $this->project;
    }

}
