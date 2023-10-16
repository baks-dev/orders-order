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

namespace BaksDev\Orders\Order\Entity\User\Delivery\Field;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Delivery\Type\Field\DeliveryFieldUid;
use BaksDev\Orders\Order\Entity\Payment\OrderPayment;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Type\Delivery\Field\OrderDeliveryFieldUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/* Перевод OrderPaymentField */


#[ORM\Entity]
#[ORM\Table(name: 'orders_delivery_field')]
class OrderDeliveryField extends EntityEvent
{
	public const TABLE = 'orders_delivery_field';
	
	/** ID */
	#[ORM\Id]
	#[ORM\Column(type: OrderDeliveryFieldUid::TYPE)]
	private OrderDeliveryFieldUid $id;
	
	/** Связь на доставку */
	#[ORM\ManyToOne(targetEntity: OrderDelivery::class, inversedBy: "field")]
	#[ORM\JoinColumn(name: 'delivery', referencedColumnName: "id")]
	private OrderDelivery $delivery;
	
	/** Идентификатор пользовательского поля в способе доставки */
	#[ORM\Column(type: DeliveryFieldUid::TYPE)]
	private DeliveryFieldUid $field;
	
	/** Заполненное значение */
	#[ORM\Column(type: Types::STRING, length: 255)]
	private string $value;
	
	
	public function __construct(OrderDelivery $delivery)
	{
		$this->id = new OrderDeliveryFieldUid();
		$this->delivery = $delivery;
	}
	
	public function __clone() : void
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

		if($dto instanceof OrderDeliveryFieldInterface)
		{
			return parent::getDto($dto);
		}
		
		throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
	}
	
	
	public function setEntity($dto): mixed
	{
		if($dto instanceof OrderDeliveryFieldInterface || $dto instanceof self)
		{
			return parent::setEntity($dto);
		}
		
		throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
	}
	
}