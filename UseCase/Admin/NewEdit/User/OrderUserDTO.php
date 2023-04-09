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

namespace BaksDev\Orders\Order\UseCase\Admin\NewEdit\User;

use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\User\OrderUserInterface;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Users\Profile\UserProfile\Type\Event\UserProfileEventUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

final class OrderUserDTO implements OrderUserInterface
{
	
	/* Пользователь  */
	
	/** ID пользователя  */
	#[Assert\Uuid]
	private ?UserUid $user = null;
	
	/* Профиль пользователя */
	
	/** Идентификатор События!! профиля пользователя */
	#[Assert\Uuid]
	private ?UserProfileEventUid $profile = null;
	
	
	/** Способ оплаты */
	#[Assert\Valid]
	private Payment\OrderPaymentDTO $payment;
	
	/** Способ доставки */
	#[Assert\Valid]
	private Delivery\OrderDeliveryDTO $delivery;
	
	
	
	public function __construct()
	{
		$this->payment = new Payment\OrderPaymentDTO;
		$this->delivery = new Delivery\OrderDeliveryDTO;
	}
	
	
	/** ID пользователя */
	public function getUser() : ?UserUid
	{
		return $this->user;
	}
	
	
	public function setUser(?UserUid $user) : void
	{
		
		$this->user = $user;
	}
	
	
	/** Идентификатор События!! профиля пользователя */
	
	public function getProfile() : ?UserProfileEventUid
	{
		
		return $this->profile;
	}
	
	
	public function setProfile(?UserProfileEventUid $profile) : void
	{
		$this->profile = $profile;
	}
	
	
	
	
	/** Способ оплаты */
	
	public function getPayment() : Payment\OrderPaymentDTO
	{
		return $this->payment;
	}
	
	
	public function setPayment(Payment\OrderPaymentDTO $payment) : void
	{
		$this->payment = $payment;
	}
	
	
	/** Способ доставки */
	
	public function getDelivery() : Delivery\OrderDeliveryDTO
	{
		return $this->delivery;
	}
	
	
	public function setDelivery(Delivery\OrderDeliveryDTO $delivery) : void
	{
		$this->delivery = $delivery;
	}
	
	

}