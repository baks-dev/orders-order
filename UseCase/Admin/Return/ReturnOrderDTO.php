<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Admin\Return;

use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusReturn;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusInterface;
use BaksDev\Orders\Order\UseCase\Admin\Return\Invariable\ReturnOrderInvariableDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\Products\ReturnOrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\Return\User\ReturnOrderUserDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderEvent */
final class ReturnOrderDTO implements OrderEventInterface
{
    /** Идентификатор события */
    #[Assert\Uuid]
    private readonly null $id;

    /** Постоянная величина */
    #[Assert\Valid]
    private ReturnOrderInvariableDTO $invariable;

    /** Статус заказа */
    private readonly OrderStatus $status;

    /**
     * Коллекция продукции в заказе
     *
     * @var ArrayCollection<ReturnOrderProductDTO> $product
     */
    #[Assert\Valid]
    private ArrayCollection $product;


    /** Пользователь */
    #[Assert\Valid]
    private ?ReturnOrderUserDTO $usr;

    /** Комментарий к заказу */
    private ?string $comment = null;

    /** Персональная скидка пользователя для заказа */
    private ?int $discount = null;

    public function __construct()
    {
        $this->id = null;
        $this->product = new ArrayCollection();
        $this->usr = new ReturnOrderUserDTO();
        $this->invariable = new ReturnOrderInvariableDTO();
        $this->status = new OrderStatus(OrderStatusReturn::class);
    }


    public function getEvent(): null
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        return $this;
    }

    public function addProduct(ReturnOrderProductDTO $product): void
    {
        /**
         * Проверяем продукт на уникальность в коллекции
         */
        $exist = $this->product->exists(function($k, ReturnOrderProductDTO $value) use ($product) {

            return $value->getProduct()->equals($product->getProduct())
                &&
                ((is_null($value->getOffer()) && is_null($product->getOffer())) || $value->getOffer()?->equals($product->getOffer()))
                &&
                ((is_null($value->getVariation()) && is_null($product->getVariation())) || $value->getVariation()?->equals($product->getVariation()))
                &&
                ((is_null($value->getModification()) && is_null($product->getModification())) || $value->getModification()?->equals($product->getModification()));
        });

        if(false === $exist)
        {
            $this->product->add($product);
        }
    }

    /**
     * Коллекция продукции в заказе
     *
     * @return ArrayCollection<ReturnOrderProductDTO>
     */
    public function getProduct(): ArrayCollection
    {
        return $this->product;
    }

    public function setProduct(ArrayCollection $product): void
    {
        $this->product = $product;
    }

    public function removeProduct(ReturnOrderProductDTO $product): void
    {
        $this->product->removeElement($product);
    }


    /**
     * Пользователь
     */

    public function getUsr(): ?ReturnOrderUserDTO
    {
        return $this->usr;
    }


    public function setUsr(?ReturnOrderUserDTO $users): void
    {
        $this->usr = $users;
    }

    /**
     * Статус заказа
     */

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus|OrderStatusInterface|string $status): self
    {
        return $this;
    }

    public function getInvariable(): ReturnOrderInvariableDTO
    {
        return $this->invariable;
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

    public function addComment(?string $comment): self
    {
        $this->comment .= ', '.$comment;
        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(?int $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    /**
     * OrderNumber
     */
    public function getOrderNumber(): ?string
    {
        return $this->invariable->getNumber();
    }
}
