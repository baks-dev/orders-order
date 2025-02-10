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

namespace BaksDev\Orders\Order\Entity\User\Delivery;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Delivery\Type\Event\DeliveryEventUid;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Delivery\OrderDeliveryUid;
use BaksDev\Reference\Money\Type\Money;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

// Модификаторы событий OrderDelivery

#[ORM\Entity]
#[ORM\Table(name: 'orders_delivery')]
class OrderDelivery extends EntityEvent
{
    /** ID */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\Column(type: OrderDeliveryUid::TYPE)]
    private OrderDeliveryUid $id;

    /** ID пользователя заказа */

    #[ORM\OneToOne(targetEntity: OrderUser::class, inversedBy: 'delivery')]
    #[ORM\JoinColumn(name: 'usr', referencedColumnName: 'id')]
    private OrderUser $usr;

    /** Способ доставки */
    #[ORM\Column(type: DeliveryUid::TYPE)]
    private DeliveryUid $delivery;

    /** Событие способа доставки (для расчета стоимости) */
    #[ORM\Column(type: DeliveryEventUid::TYPE)]
    private DeliveryEventUid $event;

    /** Пользовательские поля */
    #[ORM\OneToMany(targetEntity: Field\OrderDeliveryField::class, mappedBy: 'delivery', cascade: ['all'])]
    private Collection $field;

    /** Координаты адреса доставки */

    /** GPS широта:*/
    #[ORM\Column(type: GpsLatitude::TYPE, nullable: true)]
    private ?GpsLatitude $latitude = null;

    /** GPS долгота:*/
    #[ORM\Column(type: GpsLongitude::TYPE, nullable: true)]
    private ?GpsLongitude $longitude = null;

    /** Дата доставки заказа */
    // #[Assert\NotBlank]
    #[ORM\Column(name: 'delivery_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $deliveryDate = null;

    /** Стоимость покупки */
    #[ORM\OneToOne(targetEntity: Price\OrderDeliveryPrice::class, mappedBy: 'delivery', cascade: ['all'])]
    private ?Price\OrderDeliveryPrice $price = null;

    public function __construct(OrderUser $usr)
    {
        $this->id = new OrderDeliveryUid();
        $this->usr = $usr;
    }

    public function __clone(): void
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OrderDeliveryInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderDeliveryInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setDeliveryFree(): self
    {
        $this->price = null;
        return $this;
    }

    /**
     * Delivery
     */
    public function getDeliveryType(): DeliveryUid
    {
        return $this->delivery;
    }
}
