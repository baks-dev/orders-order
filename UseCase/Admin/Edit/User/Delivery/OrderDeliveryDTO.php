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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Delivery\Type\Event\DeliveryEventUid;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDeliveryInterface;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery\Price\OrderDeliveryPriceDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery\Field\OrderDeliveryFieldDTO;

final class OrderDeliveryDTO implements OrderDeliveryInterface
{
    /** Способ доставки */
    #[Assert\NotBlank]
    private ?DeliveryUid $delivery = null;

    /** Событие способа доставки (для расчета стоимости) */
    #[Assert\NotBlank]
    private DeliveryEventUid $event;

    /** Пользовательские поля */
    #[Assert\Valid]
    private ArrayCollection $field;

    /**
     * GPS широта.
     */
    #[Assert\NotBlank]
    private ?GpsLatitude $latitude = null;

    /**
     * GPS долгота.
     */
    #[Assert\NotBlank]
    private ?GpsLongitude $longitude = null;


    /** Дата доставки заказа */
    #[Assert\NotBlank]
    private ?DateTimeImmutable $deliveryDate;

    /** Стоимость доставки заказа */
    #[Assert\Valid]
    private OrderDeliveryPriceDTO $price;


    public function __construct()
    {
        $this->field = new ArrayCollection();
        $this->price = new OrderDeliveryPriceDTO();

        $now = (new DateTimeImmutable())->setTime(0, 0, 0);
        $this->deliveryDate = $now->add(new DateInterval('P1D'));
    }

    /** Способ доставки */
    public function getDelivery(): ?DeliveryUid
    {
        return $this->delivery;
    }

    public function setDelivery(DeliveryUid $delivery): void
    {
        $this->delivery = $delivery;
    }

    /** Событие способа оплаты (для расчета стоимости) */
    public function getEvent(): DeliveryEventUid
    {
        return $this->event;
    }

    public function setEvent(DeliveryEventUid $event): void
    {
        $this->event = $event;
    }

    /** Пользовательские поля */
    public function getField(): ArrayCollection
    {
        return $this->field;
    }

    public function setField(ArrayCollection $field): void
    {
        $this->field = $field;
    }

    public function addField(OrderDeliveryFieldDTO $field): void
    {
        if(!$this->field->contains($field))
        {
            $this->field->add($field);
        }
    }

    public function removeField(OrderDeliveryFieldDTO $field): void
    {
        $this->field->removeElement($field);
    }

    /**
     * GPS широта.
     */
    public function getLatitude(): ?GpsLatitude
    {
        return $this->latitude;
    }

    public function setLatitude(?GpsLatitude $latitude): void
    {
        $this->latitude = $latitude;
    }


    /**
     * GPS долгота.
     */
    public function getLongitude(): ?GpsLongitude
    {
        return $this->longitude;
    }

    public function setLongitude(?GpsLongitude $longitude): void
    {
        $this->longitude = $longitude;
    }


    /**
     * DeliveryDate
     */
    public function getDeliveryDate(): ?DateTimeImmutable
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(?DateTimeImmutable $deliveryDate): self
    {
        /** Указываем дату доставки на следующий день */
        if(!$deliveryDate)
        {
            $now = (new DateTimeImmutable())->setTime(0, 0, 0);
            $deliveryDate = $now->add(new DateInterval('P1D'));
        }

        $this->deliveryDate = $deliveryDate;
        return $this;
    }

    /**
     * Price
     */
    public function getPrice(): OrderDeliveryPriceDTO
    {
        return $this->price;
    }

    public function setPrice(OrderDeliveryPriceDTO $price): self
    {
        $this->price = $price;
        return $this;
    }

}
