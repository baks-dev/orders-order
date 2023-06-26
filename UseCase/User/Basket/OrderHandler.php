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

namespace BaksDev\Orders\Order\UseCase\User\Basket;

use BaksDev\Auth\Email\Entity\Account;
use BaksDev\Auth\Email\UseCase\User\Registration\RegistrationHandler;
use BaksDev\Core\Services\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity as OrderEntity;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\UseCase\User\Basket\User\UserAccount\UserAccountDTO;
use BaksDev\Orders\Order\UseCase\User\Basket\User\UserProfile\UserProfileDTO;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\UserProfileHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderHandler
{
	private EntityManagerInterface $entityManager;
	
	private ValidatorInterface $validator;
	
	private LoggerInterface $logger;
	
	private RegistrationHandler $registrationHandler;
	
	private UserProfileHandler $profileHandler;
    private MessageDispatchInterface $messageDispatch;


    public function __construct(
		EntityManagerInterface $entityManager,
		ValidatorInterface $validator,
		LoggerInterface $logger,
		RegistrationHandler $registrationHandler,
		UserProfileHandler $profileHandler,
        MessageDispatchInterface $messageDispatch

	)
	{
		$this->entityManager = $entityManager;
		$this->validator = $validator;
		$this->logger = $logger;
		$this->registrationHandler = $registrationHandler;
		$this->profileHandler = $profileHandler;
        $this->messageDispatch = $messageDispatch;
    }
	
	
	public function handle(
		OrderDTO $command,
		//?UploadedFile $cover = null
	) : string|OrderEntity\Order
	{
		/* Валидация */
		$errors = $this->validator->validate($command);
		
		if(count($errors) > 0)
		{
			$uniqid = uniqid('', false);
			$errorsString = (string) $errors;
			$this->logger->error($uniqid.': '.$errorsString);
			
			return $uniqid;
		}
		
		if($command->getEvent())
		{
			$EventRepo = $this->entityManager->getRepository(OrderEntity\Event\OrderEvent::class)->find(
				$command->getEvent()
			);
			
			if($EventRepo === null)
			{
				$uniqid = uniqid('', false);
				$errorsString = sprintf(
					'Not found %s by id: %s',
					OrderEntity\Event\OrderEvent::class,
					$command->getEvent()
				);
				$this->logger->error($uniqid.': '.$errorsString);
				
				return $uniqid;
			}
			
			$Event = $EventRepo->cloneEntity();
			
		}
		else
		{
			$Event = new OrderEntity\Event\OrderEvent();
			$this->entityManager->persist($Event);
		}
		
		$this->entityManager->clear();
		
		$OrderUserDTO = $command->getUsers();
		
		/** Создаем аккаунт для авторизации */
		if($OrderUserDTO->getUser() === null)
		{
			$UserAccount = $OrderUserDTO->getUserAccount();
			
			if($UserAccount === null)
			{
				$uniqid = uniqid('', false);
				$errorsString = sprintf(
					'Not empty class %s',
					UserAccountDTO::class
				);
				$this->logger->error($uniqid.': '.$errorsString);
				
				return $uniqid;
			}
			
			$Account = $this->registrationHandler->handle($UserAccount);
			
			/* В случае шибки регистрации - возвращает код ошибки */
			if(!$Account instanceof Account)
			{
				return 'Данные авторизации '.$Account;
			}
			
			/* Присваиваем пользователя заказу */
			$OrderUserDTO->setUser($Account->getId());
		}
		
		/** Создаем профиль пользователя */
		if($OrderUserDTO->getProfile() === null)
		{
			$UserProfileDTO = $OrderUserDTO->getUserProfile();
			
			if($UserProfileDTO === null)
			{
				$uniqid = uniqid('', false);
				$errorsString = sprintf(
					'Not empty class %s',
					UserProfileDTO::class
				);
				$this->logger->error($uniqid.': '.$errorsString);
				
				return $uniqid;
			}
			
			/* Присваиваем новому профилю идентификатор пользователя (либо нового, либо уже созданного) */
			$UserProfileDTO->getInfo()->setUser($OrderUserDTO->getUser() ?: $Account->getId());
			
			$UserProfile = $this->profileHandler->handle($UserProfileDTO);
			
			if(!$UserProfile instanceof UserProfile)
			{
				return 'Профиль пользователя '.$UserProfile;
			}
			
			/* Присваиваем профиль пользователя заказу */
			$OrderUserDTO->setProfile($UserProfile->getEvent());
		}
		
		/** @var OrderEntity\Order $Main */
		if($Event->getOrders())
		{
			$Main = $this->entityManager->getRepository(OrderEntity\Order::class)->findOneBy(
				['event' => $command->getEvent()]
			);
			
			if(empty($Main))
			{
				$uniqid = uniqid('', false);
				$errorsString = sprintf(
					'Not found %s by event: %s',
					OrderEntity\Order::class,
					$command->getEvent()
				);
				$this->logger->error($uniqid.': '.$errorsString);
				
				return $uniqid;
			}
			
		}
		else
		{
			
			$Main = new OrderEntity\Order();
			$this->entityManager->persist($Main);
			$Event->setOrders($Main);
		}
		
		$Event->setEntity($command);
		$this->entityManager->persist($Event);
		
		/* присваиваем событие корню */
		$Main->setEvent($Event);
		
		
		$this->entityManager->flush();


        /* Отправляем событие в шину  */
        $this->messageDispatch->dispatch(
            message: new OrderMessage($Main->getId(), $Main->getEvent(), $command->getEvent()),
            transport: 'orders'
        );
		

		
		return $Main;
	}
	
}