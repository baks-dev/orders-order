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

declare(strict_types=1);

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\User;

use BaksDev\Orders\Order\Entity\User\OrderUserInterface;
use BaksDev\Users\Profile\UserProfile\Type\Event\UserProfileEventUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\Validator\Constraints as Assert;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Payment\OrderPaymentDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery\OrderDeliveryDTO;

final class OrderUserDTO implements OrderUserInterface
{
    /* Пользователь  */

    /** ID пользователя  */
    #[Assert\Uuid]
    private ?UserUid $usr = null;

    /* Профиль пользователя */

    /** Идентификатор События!! профиля пользователя */
    #[Assert\Uuid]
    private ?UserProfileEventUid $profile = null;


    /** Способ оплаты */
    #[Assert\Valid]
    private OrderPaymentDTO $payment;

    /** Способ доставки */
    #[Assert\Valid]
    private OrderDeliveryDTO $delivery;


    public function __construct()
    {
        $this->payment = new OrderPaymentDTO();
        $this->delivery = new OrderDeliveryDTO();
    }


    /** ID пользователя */
    public function getUsr(): ?UserUid
    {
        return $this->usr;
    }


    public function setUsr(?UserUid $usr): void
    {

        $this->usr = $usr;
    }


    /** Идентификатор События!! профиля пользователя */

    public function getProfile(): ?UserProfileEventUid
    {

        return $this->profile;
    }


    public function setProfile(?UserProfileEventUid $profile): void
    {
        $this->profile = $profile;
    }


    /** Способ оплаты */

    public function getPayment(): OrderPaymentDTO
    {
        return $this->payment;
    }


    public function setPayment(OrderPaymentDTO $payment): void
    {
        $this->payment = $payment;
    }


    /** Способ доставки */

    public function getDelivery(): OrderDeliveryDTO
    {
        return $this->delivery;
    }


    public function setDelivery(OrderDeliveryDTO $delivery): void
    {
        $this->delivery = $delivery;
    }


}
