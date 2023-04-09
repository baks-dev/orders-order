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

namespace BaksDev\Orders\Order\UseCase\User\Basket\User\Delivery;

use BaksDev\Delivery\Type\Event\DeliveryEventUid;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDeliveryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderDeliveryDTO implements OrderDeliveryInterface
{
	
	/** Способ оплаты */
	#[Assert\NotBlank]
	private ?DeliveryUid $delivery = null;
	
	/** Событие способа оплаты (для расчета стоимости) */
	#[Assert\NotBlank]
	private DeliveryEventUid $event;
	
	/** Пользовательские поля */
	#[Assert\Valid]
	private ArrayCollection $field;
	
	
	public function __construct()
	{
		
		$this->field = new ArrayCollection();
		
	}
	
	
	/** Способ доставки */
	
	public function getDelivery() : ?DeliveryUid
	{
		return $this->delivery;
	}
	
	
	public function setDelivery(DeliveryUid $delivery) : void
	{
		$this->delivery = $delivery;
	}
	
	
	/** Событие способа оплаты (для расчета стоимости) */
	
	public function getEvent() : DeliveryEventUid
	{
		return $this->event;
	}

	public function setEvent(DeliveryEventUid $event) : void
	{
		$this->event = $event;
	}
	
	
	
	
	/** Пользовательские поля */
	
	public function getField() : ArrayCollection
	{
		return $this->field;
	}
	
	
	public function setField(ArrayCollection $field) : void
	{
		$this->field = $field;
	}
	
	
	public function addField(Field\OrderDeliveryFieldDTO $field) : void
	{
		if(!$this->field->contains($field))
		{
			$this->field->add($field);
		}
	}
	
	
	public function removeField(Field\OrderDeliveryFieldDTO $field) : void
	{
		$this->field->removeElement($field);
	}
	
}