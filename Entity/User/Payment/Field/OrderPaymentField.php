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

namespace BaksDev\Orders\Order\Entity\User\Payment\Field;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Orders\Order\Entity\User\Payment\OrderPayment;
use BaksDev\Orders\Order\Type\Payment\Field\OrderPaymentFieldUid;
use BaksDev\Payment\Type\Field\PaymentFieldUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/* Перевод OrderPaymentField */

#[ORM\Entity]
#[ORM\Table(name: 'orders_payment_field')]
class OrderPaymentField extends EntityEvent
{
    public const TABLE = 'orders_payment_field';

    /** ID */
    #[ORM\Id]
    #[ORM\Column(type: OrderPaymentFieldUid::TYPE)]
    private OrderPaymentFieldUid $id;

    /** Связь на событие */
    #[ORM\ManyToOne(targetEntity: OrderPayment::class, inversedBy: "field")]
    #[ORM\JoinColumn(name: 'payment', referencedColumnName: "id")]
    private OrderPayment $payment;

    /** Идентификатор пользовательского поля в способе оплаты */
    #[ORM\Column(type: PaymentFieldUid::TYPE)]
    private PaymentFieldUid $field;

    /** Заполненное значение */
    #[ORM\Column(type: Types::STRING)]
    private string $value;


    public function __construct(OrderPayment $payment)
    {
        $this->id = new OrderPaymentFieldUid();
        $this->payment = $payment;
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

        if($dto instanceof OrderPaymentFieldInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {

        if($dto instanceof OrderPaymentFieldInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

}
