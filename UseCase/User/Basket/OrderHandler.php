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
use BaksDev\Auth\Email\Entity\Event\AccountEvent;
use BaksDev\Auth\Email\Repository\AccountEventActiveByEmail\AccountEventActiveByEmailInterface;
use BaksDev\Auth\Email\UseCase\User\Registration\RegistrationHandler;
use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\UseCase\User\Basket\User\UserAccount\UserAccountDTO;
use BaksDev\Orders\Order\UseCase\User\Basket\User\UserProfile\UserProfileDTO;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\CurrentUserProfileEvent\CurrentUserProfileEventInterface;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\UserProfileHandler;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderHandler extends AbstractHandler
{
    private RegistrationHandler $registrationHandler;
    private UserProfileHandler $profileHandler;
    private AccountEventActiveByEmailInterface $accountEventActiveByEmail;
    private UserPasswordHasherInterface $passwordHasher;
    private CurrentUserProfileEventInterface $currentUserProfileEvent;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload,
        RegistrationHandler $registrationHandler,
        UserProfileHandler $profileHandler,
        AccountEventActiveByEmailInterface $accountEventActiveByEmail,
        UserPasswordHasherInterface $passwordHasher,
        CurrentUserProfileEventInterface $currentUserProfileEvent,
        TokenStorageInterface $tokenStorage
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);

        $this->registrationHandler = $registrationHandler;
        $this->profileHandler = $profileHandler;
        $this->accountEventActiveByEmail = $accountEventActiveByEmail;
        $this->passwordHasher = $passwordHasher;
        $this->currentUserProfileEvent = $currentUserProfileEvent;
        $this->tokenStorage = $tokenStorage;
    }


    public function handle(OrderDTO $command): string|Order
    {





        //$UserAccount = $OrderUserDTO->getUserAccount();

        //dd($UserAccount);


        /**
         * Создаем аккаунт для авторизации если отсутствует
         */
//        if(!$UserAccount->getEvent())
//        {
//
//        }


        //dd($UserAccount->getEvent());



//        if($OrderUserDTO->getUsr() === null)
//        {
//
//            $this->validatorCollection->add($UserAccount);
//
//            if($UserAccount === null)
//            {
//                return $this->validatorCollection->getErrorUniqid();
//            }
//
//
//            /**
//             * Пробуем по указанным данным авторизовать пользователя либо регистрируем в случае неудачи
//             */
//            $UserUid = $this->authenticate($UserAccount)?->getAccount();
//
//            if(!$UserUid)
//            {
//                $Account = $this->registrationHandler->handle($UserAccount);
//
//                /** Возвращаем ошибку регистрации нового пользователя */
//                if(!$Account instanceof Account)
//                {
//                    return $Account;
//                }
//
//                $UserUid = $Account->getId();
//            }
//
//            /* Присваиваем пользователя заказу */
//            $OrderUserDTO->setUsr($UserUid);
//

//        }


        /* Валидация DTO  */
        $this->validatorCollection->add($command);

        $OrderUserDTO = $command->getUsr();

        /**
         * Создаем профиль пользователя если отсутствует
         */
        if($OrderUserDTO->getProfile() === null)
        {

            $UserProfileDTO = $OrderUserDTO->getUserProfile();

            //dd($UserProfileDTO);

            $this->validatorCollection->add($UserProfileDTO);

            if($UserProfileDTO === null)
            {
                return $this->validatorCollection->getErrorUniqid();
            }

            /** Пробуем найти активный профиль пользователя */
            $UserProfileEvent = $this->currentUserProfileEvent
                ->findByUser($OrderUserDTO->getUsr())?->getId();

            if(!$UserProfileEvent)
            {
                /* Присваиваем новому профилю идентификатор пользователя (либо нового, либо уже созданного) */

                //$InfoDTO = $UserProfileDTO->getInfo();


                $UserProfileDTO->getInfo()->setUsr($OrderUserDTO->getUsr());

                $UserProfile = $this->profileHandler->handle($UserProfileDTO);

                if(!$UserProfile instanceof UserProfile)
                {
                    return $UserProfile;
                }

                $UserProfileEvent = $UserProfile->getEvent();

                //dd($UserProfileEvent);

            }

            $OrderUserDTO->setProfile($UserProfileEvent);
        }


        $this->main = new Order();
        $this->event = new OrderEvent();

        try
        {
            $command->getEvent() ? $this->preUpdate($command, true) : $this->prePersist($command);
        }
        catch(DomainException $errorUniqid)
        {
            return $errorUniqid->getMessage();
        }

        /* Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }


        //dump($command);

        //dump($this->main);
        //dd($this->event);

        $this->entityManager->flush();






        /* Отправляем сообщение в шину */
        $this->messageDispatch->dispatch(
            message: new OrderMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
            transport: 'orders-order'
        );

        return $this->main;
    }


//    public function _authenticate(UserAccountDTO $UserAccount): ?AccountEvent
//    {
//        /** Пробуем авторизовать пользователя по указанным данным */
//
//        $Account = $this->accountEventActiveByEmail->getAccountEvent($UserAccount->getEmail());
//
//        if($Account === null)
//        {
//            return null;
//        }
//
//        /* Проверяем пароль */
//        $passValid = $this->passwordHasher->isPasswordValid($Account, $UserAccount->getPasswordPlain());
//
//        if($passValid === false)
//        {
//            return null;
//        }
//
//        return $Account;
//    }

//    public function _authorization(UserUid $user): void
//    {
//        $CurrentUser = new User((string) $user);
//
//        $authorizationToken = new  UsernamePasswordToken(
//            $CurrentUser,
//            "user",
//            ['ROLE_USER']
//        );
//
//        $this->tokenStorage->setToken($authorizationToken);
//    }



//    public function OLDhandle(OrderDTO $command,): string|Order
//    {
//        /* Валидация DTO */
//        $errors = $this->validator->validate($command);
//
//        if(count($errors) > 0)
//        {
//            /** Ошибка валидации */
//            $uniqid = uniqid('', false);
//            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__FILE__.':'.__LINE__]);
//
//            return $uniqid;
//        }
//
//        if($command->getEvent())
//        {
//            $EventRepo = $this->entityManager->getRepository(OrderEvent::class)->find(
//                $command->getEvent()
//            );
//
//            if($EventRepo === null)
//            {
//                $uniqid = uniqid('', false);
//                $errorsString = sprintf(
//                    'Not found %s by id: %s',
//                    OrderEvent::class,
//                    $command->getEvent()
//                );
//                $this->logger->error($uniqid.': '.$errorsString);
//
//                return $uniqid;
//            }
//
//            $EventRepo->setEntity($command);
//            $EventRepo->setEntityManager($this->entityManager);
//            $Event = $EventRepo->cloneEntity();
//        }
//        else
//        {
//            $Event = new OrderEvent();
//            $Event->setEntity($command);
//            $this->entityManager->persist($Event);
//        }
//
//        //        $this->entityManager->clear();
//        //        $this->entityManager->persist($Event);
//
//
//        $OrderUserDTO = $command->getUsr();
//
//        /** Создаем аккаунт для авторизации */
//        if($OrderUserDTO->getUsr() === null)
//        {
//            $UserAccount = $OrderUserDTO->getUserAccount();
//
//            if($UserAccount === null)
//            {
//                $uniqid = uniqid('', false);
//                $errorsString = sprintf(
//                    'Not empty class %s',
//                    UserAccountDTO::class
//                );
//                $this->logger->error($uniqid.': '.$errorsString);
//
//                return $uniqid;
//            }
//
//            $Account = $this->registrationHandler->handle($UserAccount);
//
//            /* В случае шибки регистрации - возвращает код ошибки */
//            if(!$Account instanceof Account)
//            {
//                return 'Данные авторизации '.$Account;
//            }
//
//            /* Присваиваем пользователя заказу */
//            $OrderUserDTO->setUsr($Account->getId());
//        }
//
//        /** Создаем профиль пользователя */
//        if($OrderUserDTO->getProfile() === null)
//        {
//            $UserProfileDTO = $OrderUserDTO->getUserProfile();
//
//            if($UserProfileDTO === null)
//            {
//                $uniqid = uniqid('', false);
//                $errorsString = sprintf(
//                    'Not empty class %s',
//                    UserProfileDTO::class
//                );
//                $this->logger->error($uniqid.': '.$errorsString);
//
//                return $uniqid;
//            }
//
//            /* Присваиваем новому профилю идентификатор пользователя (либо нового, либо уже созданного) */
//            $UserProfileDTO->getInfo()->setUsr($OrderUserDTO->getUsr() ?: $Account->getId());
//
//            $UserProfile = $this->profileHandler->handle($UserProfileDTO);
//
//            if(!$UserProfile instanceof UserProfile)
//            {
//                return 'Профиль пользователя '.$UserProfile;
//            }
//
//            /* Присваиваем профиль пользователя заказу */
//            $OrderUserDTO->setProfile($UserProfile->getEvent());
//        }
//
//        /** @var Order $Main */
//        if($Event->getOrders())
//        {
//            $Main = $this->entityManager->getRepository(Order::class)
//                ->findOneBy(['event' => $command->getEvent()]);
//
//            if(empty($Main))
//            {
//                $uniqid = uniqid('', false);
//                $errorsString = sprintf(
//                    'Not found %s by event: %s',
//                    Order::class,
//                    $command->getEvent()
//                );
//                $this->logger->error($uniqid.': '.$errorsString);
//
//                return $uniqid;
//            }
//
//        }
//        else
//        {
//            $Main = new Order();
//            $this->entityManager->persist($Main);
//            $Event->setMain($Main);
//        }
//
//        /* присваиваем событие корню */
//        $Main->setEvent($Event);
//
//
//        /**
//         * Валидация Event
//         */
//
//        $errors = $this->validator->validate($Event);
//
//        if(count($errors) > 0)
//        {
//            /** Ошибка валидации */
//            $uniqid = uniqid('', false);
//            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__FILE__.':'.__LINE__]);
//
//            return $uniqid;
//        }
//
//
//        $this->entityManager->flush();
//
//
//        /* Отправляем событие в шину  */
//        $this->messageDispatch->dispatch(
//            message: new OrderMessage($Main->getId(), $Main->getEvent(), $command->getEvent()),
//            transport: 'orders-order'
//        );
//
//
//        return $Main;
//    }

}