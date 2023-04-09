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

namespace BaksDev\Orders\Order\Entity\User\Delivery;

use BaksDev\Delivery\Type\Event\DeliveryEventUid;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Delivery\OrderDeliveryUid;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;
use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Entity\EntityState;
use BaksDev\Core\Type\Ip\IpAddress;
use BaksDev\Core\Type\Modify\ModifyAction;
use BaksDev\Core\Type\Modify\ModifyActionEnum;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/* Модификаторы событий OrderDelivery */


#[ORM\Entity]
#[ORM\Table(name: 'orders_delivery')]
class OrderDelivery extends EntityEvent
{
	public const TABLE = 'orders_delivery';
	
	/** ID */
	#[ORM\Id]
	#[ORM\Column(type: OrderDeliveryUid::TYPE)]
	private OrderDeliveryUid $id;
	
	/** ID пользователя заказа */
	#[ORM\OneToOne(inversedBy: 'delivery', targetEntity: OrderUser::class)]
	#[ORM\JoinColumn(name: 'orders_user', referencedColumnName: 'id')]
	private OrderUser $user;
	
	/** Способ оплаты */
	#[ORM\Column(type: DeliveryUid::TYPE)]
	private DeliveryUid $delivery;
	
	/** Событие способа доставки (для расчета стоимости) */
	#[ORM\Column(type: DeliveryEventUid::TYPE)]
	private DeliveryEventUid $event;
	
	/** Пользовательские поля */
	#[ORM\OneToMany(mappedBy: 'delivery', targetEntity: Field\OrderDeliveryField::class, cascade: ['all'])]
	private Collection $field;
	
	
	public function __construct(OrderUser $user)
	{
		$this->id = new OrderDeliveryUid();
		$this->user = $user;
		
	}
	
	public function __clone() : void
	{
		$this->id = new OrderDeliveryUid();
	}
	
	
	public function getDto($dto) : mixed
	{
		if($dto instanceof OrderDeliveryInterface)
		{
			return parent::getDto($dto);
		}
		
		throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
	}
	
	
	public function setEntity($dto) : mixed
	{
		if($dto instanceof OrderDeliveryInterface)
		{
			return parent::setEntity($dto);
		}
		
		throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
	}
	
}