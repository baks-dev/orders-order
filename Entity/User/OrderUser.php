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

namespace BaksDev\Orders\Order\Entity\User;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\Payment\OrderPayment;
use BaksDev\Orders\Order\Type\User\OrderUserUid;
use BaksDev\Users\Profile\UserProfile\Type\Event\UserProfileEventUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/* Пользователь, которому принадлежит заказ */

#[ORM\Entity]
#[ORM\Table(name: 'orders_user')]
class OrderUser extends EntityEvent
{
    /** ID */
    #[ORM\Id]
    #[ORM\Column(type: OrderUserUid::TYPE)]
    private OrderUserUid $id;

    /** ID события */
    #[ORM\OneToOne(targetEntity: OrderEvent::class, inversedBy: 'usr')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private OrderEvent $event;

    /** ID пользователя  */
    #[ORM\Column(type: UserUid::TYPE)]
    private UserUid $usr;

    /** Идентификатор События!! профиля клиента */
    #[ORM\Column(type: UserProfileEventUid::TYPE)]
    private UserProfileEventUid $profile;

    /** Способ оплаты */
    #[ORM\OneToOne(targetEntity: OrderPayment::class, mappedBy: 'usr', cascade: ['all'], fetch: 'EAGER')]
    private OrderPayment $payment;

    /** Способ доставки */
    #[ORM\OneToOne(targetEntity: OrderDelivery::class, mappedBy: 'usr', cascade: ['all'], fetch: 'EAGER')]
    private OrderDelivery $delivery;

    public function __construct(OrderEvent $event)
    {
        $this->id = new OrderUserUid();
        $this->event = $event;

        $this->payment = new OrderPayment($this);
        $this->delivery = new OrderDelivery($this);
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

        if($dto instanceof OrderUserInterface)
        {
            return parent::getDto($dto);
        }

        return false;

        //throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderUserInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /**
     * Delivery.
     */
    public function getDelivery(): OrderDelivery
    {
        return $this->delivery;
    }

    public function getPayment(): OrderPayment
    {
        return $this->payment;
    }

    public function getClientProfile(): UserProfileEventUid
    {
        return $this->profile;
    }
}
