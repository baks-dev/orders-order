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

namespace BaksDev\Orders\Order\Messenger\Order;

use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\OrderProfile\UpdateOrderProfileInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCompleted;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Orders\Order\UseCase\User\Basket\User\Delivery\OrderDeliveryDTO;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Repository\CurrentQuantity\CurrentQuantityByEventInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Modification\CurrentQuantityByModificationInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Offer\CurrentQuantityByOfferInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Variation\CurrentQuantityByVariationInterface;
use BaksDev\Users\Address\Services\GeocodeDistance;
use BaksDev\Users\Profile\Group\BaksDevUsersProfileGroupBundle;
use BaksDev\Users\Profile\Group\Repository\ProfilesByRole\ProfilesByRoleInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileGps\UserProfileGpsInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 100)]
final class OrderNewUpdateProfile
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private UpdateOrderProfileInterface $updateOrderProfile;
    private ProfilesByRoleInterface $profilesByRole;
    private UserProfileGpsInterface $userProfileGps;
    private GeocodeDistance $geocodeDistance;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $ordersOrderLogger,
        UpdateOrderProfileInterface $updateOrderProfile,
        ProfilesByRoleInterface $profilesByRole,
        UserProfileGpsInterface $userProfileGps,
        GeocodeDistance $geocodeDistance,
    )
    {
        $this->entityManager = $entityManager;
        $this->entityManager->clear();

        $this->logger = $ordersOrderLogger;
        $this->updateOrderProfile = $updateOrderProfile;
        $this->profilesByRole = $profilesByRole;
        $this->userProfileGps = $userProfileGps;
        $this->geocodeDistance = $geocodeDistance;
    }

    /**
     * Сообщение присваивает ближайший к заказу профиль ответственного пользователя
     */
    public function __invoke(OrderMessage $message): void
    {
        /** Только при наличии групп профилей */
        if(!class_exists(BaksDevUsersProfileGroupBundle::class))
        {
            return;
        }

        $OrderEvent = $this->entityManager->getRepository(OrderEvent::class)->find($message->getEvent());

        if(!$OrderEvent)
        {
            return;
        }

        /** Если статус заказа не New «Новый»  */
        if(false === $OrderEvent->getStatus()->equals(OrderStatusNew::class))
        {
            return;
        }

        $this->handle($OrderEvent);
    }


    public function handle(OrderEvent $event): void
    {
        /**
         * Получаем все профили пользователей, имеющие права доступа (без доверенностей) ROLE_ORDERS_STATUS_NEW
         */

        $profiles = $this->profilesByRole->findAll('ROLE_ORDERS_STATUS_NEW');

        $OrderDelivery = $event->getDelivery();

        if(!$OrderDelivery)
        {
            $this->logger->notice('Не присваиваем заказу профиль ответственного: отсутствуют информация о доставке', [__FILE__.':'.__LINE__]);
            return;
        }

        $OrderDeliveryDTO = new OrderDeliveryDTO();
        $OrderDelivery->getDto($OrderDeliveryDTO);

        if(!$OrderDeliveryDTO->getLatitude() || !$OrderDeliveryDTO->getLongitude())
        {
            $this->logger->notice('Не присваиваем заказу профиль ответственного: отсутствуют GEO данные доставки заказа', [__FILE__.':'.__LINE__]);
            return;
        }

        /**
         * Определяем ближайший профиль к заказу
         */

        $distanceProfileCollection = [];

        foreach($profiles as $profile)
        {
            $UserProfileGps = $this->userProfileGps->findUserProfileGps($profile);

            if(!$UserProfileGps)
            {
                continue;
            }

            $geocodeDistance = $this->geocodeDistance
                ->fromLatitude((float) $UserProfileGps['latitude'])
                ->fromLongitude((float) $UserProfileGps['longitude'])
                ->toLatitude($OrderDeliveryDTO->getLatitude()->getFloat())
                ->toLongitude($OrderDeliveryDTO->getLongitude()->getFloat())
                ->getDistance();

           $distanceProfileCollection[(string) $geocodeDistance] = $profile;

        }

        ksort($distanceProfileCollection);

        /**
         * Присваиваем событию профиль ответственного
         */

        $profile = current($distanceProfileCollection);

        $this->updateOrderProfile
            ->event($event)
            ->profile($profile)
            ->update();

        $this->logger->info('Присвоили ближайший к заказу профиль ответственного',
        [
            __FILE__.':'.__LINE__,
            'event' => (string) $event,
            'profile' => (string) $profile,
        ]);
    }
}