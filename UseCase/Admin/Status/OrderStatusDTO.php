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

namespace BaksDev\Orders\Order\UseCase\Admin\Status;

use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User as UserEntity;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderStatusDTO implements OrderEventInterface
{
    /** Идентификатор события */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private OrderEventUid $id;

    /** Постоянная величина */
    #[Assert\Valid]
    private readonly Invariable\StatusOrderInvariableDTO $invariable;

    /**
     * Ответственный
     * @deprecated переносится в Invariable
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private UserProfileUid $profile;

    /** Статус заказа */
    #[Assert\NotBlank]
    private OrderStatus $status;


    public function __construct(
        OrderStatus|OrderStatusInterface|string $status,
        OrderEventUid $id,
        UserEntity|UserUid $user,
        UserProfileUid $profile
    ) {
        $this->id = $id;
        $this->status = new OrderStatus($status);

        $user = $user instanceof UserEntity ? $user->getId() : $user;
        $StatusOrderInvariableDTO = new Invariable\StatusOrderInvariableDTO();
        $StatusOrderInvariableDTO->setUsr($user);
        $StatusOrderInvariableDTO->setProfile($profile);
        $this->invariable = $StatusOrderInvariableDTO;

        $this->profile = $profile;

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
     * Invariable
     */
    public function getInvariable(): Invariable\StatusOrderInvariableDTO
    {
        return $this->invariable;
    }

}
