<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Public\Basket;

use BaksDev\Auth\Email\Entity\Account;
use BaksDev\Auth\Email\Entity\Event\AccountEvent;
use BaksDev\Auth\Email\Repository\AccountEventActiveByEmail\AccountEventActiveByEmailInterface;
use BaksDev\Auth\Email\Repository\AccountEventNotBlock\AccountEventNotBlockByEmail\AccountEventNotBlockByEmailInterface;
use BaksDev\Auth\Email\Type\Email\AccountEmail;
use BaksDev\Auth\Email\UseCase\User\Edit\AccountDTO;
use BaksDev\Auth\Email\UseCase\User\Edit\AccountHandler;
use BaksDev\Auth\Email\UseCase\User\Registration\RegistrationDTO;
use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\Items\PublicOrderProductItemDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\User\Delivery\Field\OrderDeliveryFieldDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\User\UserProfile\UserProfileDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\User\UserProfile\Value\ValueDTO;
use BaksDev\Users\Profile\TypeProfile\Type\Id\Choice\TypeProfileUser;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\CurrentUserProfileEvent\CurrentUserProfileEventInterface;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\Status\UserProfileStatusActive;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\UserProfileHandler;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use Random\Engine\Secure;

final class OrderHandler extends AbstractHandler
{
    public function __construct(
        private readonly UserProfileHandler $profileHandler,
        private readonly CurrentUserProfileEventInterface $currentUserProfileEvent,
        private readonly AccountHandler $AccountHandler,
        private readonly AccountEventNotBlockByEmailInterface $AccountEventNotBlockByEmailRepository,

        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload,
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);
    }


    public function handle(OrderDTO $command): string|Order
    {
        $OrderUserDTO = $command->getUsr();


        /** Создаем профиль пользователя если отсутствует */
        if($OrderUserDTO->getProfile() === null)
        {
            $UserProfileDTO = $OrderUserDTO->getUserProfile();

            $this->validatorCollection->add($UserProfileDTO);

            if($UserProfileDTO === null)
            {
                return $this->validatorCollection->getErrorUniqid();
            }

            /** Пробуем найти активный профиль пользователя */
            $UserProfileEvent = $this->currentUserProfileEvent
                ->findByUser($OrderUserDTO->getUsr());

            if($UserProfileEvent instanceof UserProfileEvent)
            {
                $UserProfileEvent = $UserProfileEvent->getId();
            }

            if(false === $UserProfileEvent)
            {
                /* Присваиваем новому профилю идентификатор пользователя (либо нового, либо уже созданного) */

                $UserProfileDTO->getInfo()->setUsr($OrderUserDTO->getUsr());

                $UserProfile = $this->profileHandler->handle($UserProfileDTO);

                if(!$UserProfile instanceof UserProfile)
                {
                    return $UserProfile;
                }

                $UserProfileEvent = $UserProfile->getEvent();
            }

            $OrderUserDTO->setProfile($UserProfileEvent);

            /**
             * AccountEmail
             */

            $AccountEmail = false;

            /** Если в свойствах профиля имеется тип AccountEmail - создаем аккаунт */
            if(false === $UserProfileDTO->getValue()->isEmpty())
            {
                $filter = $UserProfileDTO->getValue()->filter(function(ValueDTO $element) {
                    return $element->getType() === AccountEmail::TYPE;
                });

                /** создаем AccountEmail */
                if($filter->current() instanceof ValueDTO)
                {
                    $AccountEmail = new AccountEmail($filter->current()->getValue());
                }
            }

            /** Если в свойствах доставки имеется тип AccountEmail - создаем аккаунт */
            if(false === $AccountEmail)
            {
                $OrderDeliveryDTO = $OrderUserDTO->getDelivery();

                if(false === $OrderDeliveryDTO->getField()->isEmpty())
                {
                    $filter = $OrderDeliveryDTO->getField()->filter(function(OrderDeliveryFieldDTO $element) {
                        return filter_var($element->getValue(), FILTER_VALIDATE_EMAIL);
                    });

                    /** создаем AccountEmail  */
                    if($filter->current() instanceof OrderDeliveryFieldDTO)
                    {
                        $AccountEmail = new AccountEmail($filter->current()->getValue());
                    }
                }
            }

            /**
             * Если в свойствах доставки имеется тип AccountEmail - создаем аккаунт
             */
            if($AccountEmail instanceof AccountEmail)
            {
                $Account = $this->createAccountEmail($OrderUserDTO->getUsr(), $AccountEmail);

                /** Если аккаунт уже создан - устанавливаем профиль пользователя */
                if(false === ($Account instanceof Account))
                {
                    /** Поиск аккаунта по email */
                    $AccountEvent = $this->AccountEventNotBlockByEmailRepository
                        ->forEmail($AccountEmail)
                        ->find();

                    if($AccountEvent instanceof AccountEvent && $AccountEvent->getAccount() instanceof UserUid)
                    {
                        /** Присваиваем аккаунт заказу */
                        $OrderUserDTO->setUsr($AccountEvent->getAccount());

                        /** Пробуем найти активный профиль пользователя */
                        $UserProfileEvent = $this->currentUserProfileEvent
                            ->findByUser($OrderUserDTO->getUsr());

                        if($UserProfileEvent instanceof UserProfileEvent)
                        {
                            $UserProfileEvent = $UserProfileEvent->getId();
                            $OrderUserDTO->setProfile($UserProfileEvent);
                        }
                    }
                }
            }
        }

        /** Если профиль пользователя добавлен, но имеются незаполненные поля - присваиваем */
        elseif($OrderUserDTO->getProfile() && $profileValues = $OrderUserDTO->getUserProfile()?->getValue())
        {
            $UserProfileEvent = $this->currentUserProfileEvent->findByEvent($OrderUserDTO->getProfile());
            $UserProfileDTO = new UserProfileDTO();
            $UserProfileEvent->getDto($UserProfileDTO);

            if(false === $profileValues->isEmpty())
            {
                /** Добавляем профилю пользователя незаполненные свойства */
                foreach($profileValues as $value)
                {
                    $UserProfileDTO->addValue($value);
                }

                /** Если профиль пользовательский - делаем активным */
                if($UserProfileDTO->getType()?->getTypeProfile() instanceof TypeProfileUser)
                {
                    $UserProfileDTO->getInfo()->setStatus(UserProfileStatusActive::class);
                }

                $UserProfile = $this->profileHandler->handle($UserProfileDTO);

                if(!$UserProfile instanceof UserProfile)
                {
                    return $UserProfile;
                }

                $UserProfileEvent = $UserProfile->getEvent();
                $OrderUserDTO->setProfile($UserProfileEvent);
            }
        }


        /**
         * Создаем единицу продукта по количеству продукта в заказе
         */
        foreach($command->getProduct() as $PublicOrderProductDTO)
        {
            for($i = 0; $i < $PublicOrderProductDTO->getPrice()->getTotal(); $i++)
            {
                $item = new PublicOrderProductItemDTO();

                /**
                 * Присваиваем цену из продукта в заказе
                 */
                $item
                    ->getPrice()
                    ->setPrice($PublicOrderProductDTO->getPrice()->getPrice())
                    ->setCurrency($PublicOrderProductDTO->getPrice()->getCurrency());

                $PublicOrderProductDTO->addItem($item);
            }
        }


        $this
            ->setCommand($command)
            ->preEventPersistOrUpdate(Order::class, OrderEvent::class);

        /* Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->flush();

        /* Отправляем сообщение в шину */
        $this->messageDispatch
            ->addClearCacheOther('products-product')
            ->addClearCacheOther('orders-order-new')
            ->dispatch(
                message: new OrderMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
                transport: 'orders-order',
            );

        return $this->main;
    }


    private function createAccountEmail(UserUid $user, AccountEmail $email): Account|string
    {
        $password = new Secure()->generate();

        $AccountDTO = new AccountDTO()
            ->setUsr($user)
            ->setEmail($email)
            ->setPasswordPlain(md5($password));

        return $this->AccountHandler->handle($AccountDTO);
    }

}
