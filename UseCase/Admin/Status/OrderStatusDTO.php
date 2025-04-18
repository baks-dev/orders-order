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

namespace BaksDev\Orders\Order\UseCase\Admin\Status;

use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderEvent */
final class OrderStatusDTO implements OrderEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private OrderEventUid $id;

    //    /**
    //     * Ответственный
    //     */
    //    #[Assert\NotBlank]
    //    #[Assert\Uuid]
    //    private UserProfileUid $profile;

    /**
     * Статус заказа
     */
    #[Assert\NotBlank]
    private OrderStatus $status;

    /**
     * Модификатор
     *
     * Присваивается User в случае, если статус асинхронно меняется складской заявкой
     */
    #[Assert\Valid]
    private Modify\ModifyDTO $modify;

    private Invariable\StatusOrderInvariableDTO $invariable;

    public function __construct(
        OrderStatus|OrderStatusInterface|string $status,
        OrderEventUid $id
    )
    {
        $this->id = $id;
        $this->status = new OrderStatus($status);
        $this->modify = new Modify\ModifyDTO();
        $this->invariable = new Invariable\StatusOrderInvariableDTO();
    }

    /** Идентификатор события */
    public function getEvent(): OrderEventUid
    {
        return $this->id;
    }

    /** Статус заказа */
    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setProfile(UserProfileUid $profile): self
    {
        $this->invariable->setProfile($profile);
        return $this;
    }

    /**
     * Modify
     */
    public function getModify(): Modify\ModifyDTO
    {
        return $this->modify;
    }

    /**
     * Invariable
     */
    public function getInvariable(): Invariable\StatusOrderInvariableDTO
    {
        return $this->invariable;
    }

}
