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

namespace BaksDev\Orders\Order\Repository\OrderDetail;

use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Orders\Order\Entity AS OrderEntity;
use BaksDev\Users\Profile\TypeProfile\Entity as TypeProfileEntity;
use BaksDev\Users\Profile\UserProfile\Entity as UserProfileEntity;
use BaksDev\Delivery\Entity AS DeliveryEntity;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderDetail implements OrderDetailInterface
{
	private EntityManagerInterface $entityManager;
	
	private TranslatorInterface $translator;
	
	
	public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator,)
	{
		$this->entityManager = $entityManager;
		$this->translator = $translator;
	}
	
	public function fetchDetailOrderAssociative(OrderUid $order) : ?array
	{
		$qb = $this->entityManager->getConnection()->createQueryBuilder();
		
		/** ЛОКАЛЬ */
		$locale = new Locale($this->translator->getLocale());
		$qb->setParameter('local', $locale, Locale::TYPE);
		
		
		$qb->select('orders.id AS order_id')->addGroupBy('orders.id');
		$qb->addSelect('orders.event AS order_event')->addGroupBy('orders.event');
		$qb->addSelect('orders.number AS order_number')->addGroupBy('orders.number');
		
		$qb->from(OrderEntity\Order::TABLE, 'orders');
		
		
		$qb->addSelect('event.status AS order_status')->addGroupBy('event.status');
		$qb->join('orders', OrderEntity\Event\OrderEvent::TABLE, 'event', 'event.id = orders.event');
		
		
		$qb->leftJoin('orders',
			OrderEntity\User\OrderUser::TABLE,
			'order_user',
			'order_user.event = orders.event'
		);
		
		
		/** Доставка */
		
		$qb->leftJoin('order_user',
			OrderEntity\User\Delivery\OrderDelivery::TABLE,
			'order_delivery',
			'order_delivery.orders_user = order_user.id'
		);
		
		
		$qb->leftJoin('order_delivery',
			DeliveryEntity\Event\DeliveryEvent::TABLE,
			'delivery_event',
			'delivery_event.id = order_delivery.event'
		);
		
		
		$qb->addSelect('delivery_price.price AS delivery_price')
			->addGroupBy('delivery_price.price')
		;
		$qb->leftJoin('delivery_event',
			DeliveryEntity\Price\DeliveryPrice::TABLE,
			'delivery_price',
			'delivery_price.event = delivery_event.id'
		);
		
		
		/** Профиль пользователя */
		
		
		$qb->leftJoin('order_user',
			UserProfileEntity\Event\UserProfileEvent::TABLE,
			'user_profile',
			'user_profile.id = order_user.profile'
		);
		
		$qb->addSelect('user_profile_info.discount AS order_profile_discount')->addGroupBy('user_profile_info.discount');
		
		$qb->leftJoin('user_profile',
			UserProfileEntity\Info\UserProfileInfo::TABLE,
			'user_profile_info',
			'user_profile_info.profile = user_profile.profile'
		);
		
		
		
		
		$qb->leftJoin('user_profile',
			UserProfileEntity\Value\UserProfileValue::TABLE,
			'user_profile_value',
			'user_profile_value.event = user_profile.id'
		);
		
		$qb->leftJoin('user_profile',
			TypeProfileEntity\TypeProfile::TABLE,
			'type_profile',
			'type_profile.id = user_profile.type'
		);
		
		$qb->addSelect('type_profile_trans.name AS order_profile')->addGroupBy('type_profile_trans.name');
		$qb->leftJoin('type_profile',
			TypeProfileEntity\Trans\TypeProfileTrans::TABLE,
			'type_profile_trans',
			'type_profile_trans.event = type_profile.event AND type_profile_trans.local = :local'
		);
		
		$qb->join('user_profile_value',
			TypeProfileEntity\Section\Fields\TypeProfileSectionField::TABLE,
			'type_profile_field',
			'type_profile_field.id = user_profile_value.field AND type_profile_field.card = true'
		);
		
		$qb->leftJoin('type_profile_field',
			TypeProfileEntity\Section\Fields\Trans\TypeProfileSectionFieldTrans::TABLE,
			'type_profile_field_trans',
			'type_profile_field_trans.field = type_profile_field.id AND type_profile_field_trans.local = :local'
		);
		
		
		
		/** Автарка профиля клиента */
		$qb->addSelect("CONCAT ( '/upload/".UserProfileEntity\Avatar\UserProfileAvatar::TABLE."' , '/', profile_avatar.dir, '/', profile_avatar.name, '.') AS profile_avatar_name")
			->addGroupBy('profile_avatar.dir')
			->addGroupBy('profile_avatar.name')
		;
		
		$qb->addSelect('profile_avatar.ext AS profile_avatar_ext')->addGroupBy('profile_avatar.ext');
		$qb->addSelect('profile_avatar.cdn AS profile_avatar_cdn')->addGroupBy('profile_avatar.cdn');
		
		
		$qb->leftJoin(
			'user_profile',
			UserProfileEntity\Avatar\UserProfileAvatar::TABLE,
			'profile_avatar',
			'profile_avatar.event = user_profile.id'
		);
		
		
		
		$qb->addSelect("JSON_AGG
			( DISTINCT
				
					JSONB_BUILD_OBJECT
					(
						/* свойства для сортирвоки JSON */
						'0', type_profile_field.sort,

						'profile_type', type_profile_field.type,
						'profile_name', type_profile_field_trans.name,
						'profile_value', user_profile_value.value
					)
				
			)
			AS order_user"
		);
		
		
		$qb->where('orders.id = :order');
		$qb->setParameter('order', $order, OrderUid::TYPE);
		
		return $qb->fetchAssociative();
	}
	
	

	public function getDetailOrder(OrderUid $order) : mixed
	{
		$qb = $this->entityManager->createQueryBuilder();
		
		$qb->select('orders');
		$qb->from(OrderEntity\Order::class, 'orders');
		$qb->where('orders.id = :order');
		$qb->setParameter('order', $order, OrderUid::TYPE);
		
		return $qb->getQuery()->getOneOrNullResult();
	}
	
}