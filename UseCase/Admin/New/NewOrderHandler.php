<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Admin\New;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\CurrentUserProfileEvent\CurrentUserProfileEventInterface;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\UserProfileHandler;
use Doctrine\ORM\EntityManagerInterface;

final class NewOrderHandler extends AbstractHandler
{
    public function __construct(
        private readonly UserProfileHandler $profileHandler,
        private readonly CurrentUserProfileEventInterface $currentUserProfileEvent,

        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload,
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);
    }

    public function handle(NewOrderDTO $command): string|Order
    {
        $OrderUserDTO = $command->getUsr();

        /**
         * Создаем профиль пользователя если отсутствует
         */
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
        }


        $this
            ->setCommand($command)
            ->preEventPersistOrUpdate(Order::class, OrderEvent::class);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->flush();

        /* Отправляем сообщение в шину */
        $this->messageDispatch
            ->addClearCacheOther('products-product')
            ->dispatch(
                message: new OrderMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
                transport: 'orders-order',
            );

        return $this->main;
    }

}
