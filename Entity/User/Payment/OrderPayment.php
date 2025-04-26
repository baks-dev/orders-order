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

namespace BaksDev\Orders\Order\Entity\User\Payment;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Payment\OrderPaymentUid;
use BaksDev\Payment\Type\Id\PaymentUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/* Способ оплаты заказа */


#[ORM\Entity]
#[ORM\Table(name: 'orders_payment')]
class OrderPayment extends EntityEvent
{
    /** ID */
    #[ORM\Id]
    #[ORM\Column(type: OrderPaymentUid::TYPE)]
    private OrderPaymentUid $id;

    /** ID пользователя заказа */
    #[ORM\OneToOne(targetEntity: OrderUser::class, inversedBy: 'payment')]
    #[ORM\JoinColumn(name: 'usr', referencedColumnName: 'id')]
    private OrderUser $usr;

    /** Способ оплаты */
    #[ORM\Column(type: PaymentUid::TYPE)]
    private PaymentUid $payment;

    /** Пользовательские поля */
    #[ORM\OneToMany(targetEntity: Field\OrderPaymentField::class, mappedBy: 'payment', cascade: ['all'], fetch: 'EAGER')]
    private Collection $field;


    public function __construct(OrderUser $usr)
    {
        $this->id = new OrderPaymentUid();
        $this->usr = $usr;
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

        if($dto instanceof OrderPaymentInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderPaymentInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


}
