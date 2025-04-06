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

namespace BaksDev\Orders\Order\UseCase\Admin\New;

use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User as UserEntity;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderEvent */
final class NewOrderDTO implements OrderEventInterface
{
    /** Идентификатор события */
    #[Assert\Uuid]
    private ?OrderEventUid $id = null;

    /** Коллекция продукции в заказе */
    #[Assert\Valid]
    private ArrayCollection $product;


    private preProduct\PreProductDTO $preProduct;

    /**
     * Ответственный
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private UserProfileUid $profile;


    /** Постоянная величина */
    #[Assert\Valid]
    private readonly Invariable\NewOrderInvariableDTO $invariable;

    /** Статус заказа */
    #[Assert\NotBlank]
    private OrderStatus $status;


    /** Пользователь */
    #[Assert\Valid]
    private User\OrderUserDTO $usr;

    /** Комментарий к заказу */
    private ?string $comment = null;


    public function __construct(/*UserEntity|UserUid $user, UserProfileUid $profile*/)
    {
        $this->invariable = new Invariable\NewOrderInvariableDTO();


        $this->product = new ArrayCollection();
        $this->usr = new User\OrderUserDTO();
        $this->preProduct = new preProduct\PreProductDTO();
        $this->status = new OrderStatus(OrderStatusNew::class);
    }


    public function getEvent(): ?OrderEventUid
    {
        return $this->id;
    }

    public function resetId(): void
    {
        $this->id = null;
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

    public function addProduct(Products\NewOrderProductDTO $product): void
    {
        if(!$this->product->contains($product))
        {
            $this->product->add($product);
        }
    }

    public function removeProduct(Products\NewOrderProductDTO $product): void
    {
        $this->product->removeElement($product);
    }

    /** Статус заказа */
    public function getStatus(): OrderStatus
    {
        return $this->status;
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

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

    public function setProfile(UserProfileUid $profile): self
    {
        $this->profile = $profile;
        return $this;
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

    /**
     * PreProduct
     */
    public function getPreProduct(): preProduct\PreProductDTO
    {
        return $this->preProduct;
    }

    public function setPreProduct(preProduct\PreProductDTO $preProduct): self
    {
        $this->preProduct = $preProduct;
        return $this;
    }

    /**
     * Invariable
     */
    public function getInvariable(): Invariable\NewOrderInvariableDTO
    {
        return $this->invariable;
    }
}
