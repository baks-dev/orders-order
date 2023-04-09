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

namespace BaksDev\Orders\Order\Repository\OrderHistory;

use BaksDev\Auth\Email\Entity as AccountEntity;
use BaksDev\Orders\Order\Entity as OrderEntity;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Entity AS UserProfileEntity;
use Doctrine\DBAL\Connection;

final class OrderHistory implements OrderHistoryInterface
{
	
	private Connection $connection;
	
	
	public function __construct(
		Connection $connection,
	)
	{
		$this->connection = $connection;
	}
	
	
	public function fetchHistoryAllAssociative(OrderUid $order) : array
	{
		$qb = $this->connection->createQueryBuilder();
		
		
		$qb->addSelect('event.status');
		$qb->from(OrderEntity\Event\OrderEvent::TABLE, 'event');
		$qb->where('event.orders = :order');
		$qb->setParameter('order', $order, OrderUid::TYPE);
		
		$qb->addSelect('modify.mod_date');
		$qb->addSelect('modify.action');
		$qb->leftJoin('event', OrderEntity\Modify\OrderModify::TABLE, 'modify', 'modify.event = event.id');
		
		
		
		$qb->addSelect('order_user.profile AS order_profile_id');
		$qb->leftJoin('event', OrderEntity\User\OrderUser::TABLE, 'order_user',
			'order_user.event = event.id');
		
		
		
		
		//$qb->join('modify', AccountEntity\Account::TABLE, 'account', 'account.id = modify.user_id');
		
//		$qb->addSelect('account_event.email');
//
//		$qb->leftJoin('account',
//			AccountEntity\Event\AccountEvent::TABLE,
//			'account_event',
//			'account_event.id = account.event'
//		);
		
		$qb->leftJoin('modify',
			UserProfileEntity\Info\UserProfileInfo::TABLE,
			'profile_info',
			'profile_info.user_id = modify.user_id AND profile_info.active = true'
		);
		
		
		$qb->addSelect('profile.id AS user_profile_id'); /* ID профиля */
		//$qb->addSelect('profile.event AS user_profile_event'); /* ID события профиля */
		$qb->leftJoin(
			'profile_info',
			UserProfileEntity\UserProfile::TABLE,
			'profile',
			'profile.id = profile_info.profile'
		);
		
		$qb->addSelect('profile_personal.username AS profile_username'); /* Username */

		$qb->leftJoin(
			'profile',
			UserProfileEntity\Personal\UserProfilePersonal::TABLE,
			'profile_personal',
			'profile_personal.event = profile.event'
		);
		
		
		$qb->addSelect('profile_avatar.name AS profile_avatar_name');
		$qb->addSelect('profile_avatar.dir AS profile_avatar_dir');
		$qb->addSelect('profile_avatar.ext AS profile_avatar_ext');
		$qb->addSelect('profile_avatar.cdn AS profile_avatar_cdn');
		
		$qb->leftJoin(
			'profile',
			UserProfileEntity\Avatar\UserProfileAvatar::TABLE,
			'profile_avatar',
			'profile_avatar.event = profile.event'
		);
		
		$qb->orderBy('modify.mod_date');
		
		return $qb->fetchAllAssociative();
	}
	
}