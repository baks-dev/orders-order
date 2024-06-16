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

namespace BaksDev\Orders\Order\UseCase\Admin\Package\User\Delivery;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDeliveryInterface;
use Doctrine\Common\Collections\ArrayCollection;

final class OrderDeliveryDTO implements OrderDeliveryInterface
{
    //	/** Способ оплаты */
    //	#[Assert\NotBlank]
    //	private ?DeliveryUid $delivery = null;
    //
    //	/** Событие способа оплаты (для расчета стоимости) */
    //	#[Assert\NotBlank]
    //	private DeliveryEventUid $event;

    /** Пользовательские поля */
    #[Assert\Valid]
    private ArrayCollection $field;

    //    /** Координаты на карте */
    //    private ?GeocodeAddressUid $geocode = null;
    //

    /** GPS широта:*/
    private ?GpsLatitude $latitude = null;

    /** GPS долгота:*/
    private ?GpsLongitude $longitude = null;

    private ?string $address = null;

    private bool $pickup = false;

    /**
     * Latitude.
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
     * Longitude.
     */
    public function getLongitude(): ?GpsLongitude
    {
        return $this->longitude;
    }

    public function setLongitude(?GpsLongitude $longitude): void
    {
        $this->longitude = $longitude;
    }

    //    public function getGeocode()
    //    {
    //        return null;
    //    }

    public function __construct()
    {
        $this->field = new ArrayCollection();
    }

    //    /** Способ доставки */
    //    public function getDelivery(): ?DeliveryUid
    //    {
    //        return $this->delivery;
    //    }
    //
    //    public function setDelivery(DeliveryUid $delivery): void
    //    {
    //        $this->delivery = $delivery;
    //    }

    //    /** Событие способа оплаты (для расчета стоимости) */
    //    public function getEvent(): DeliveryEventUid
    //    {
    //        return $this->event;
    //    }
    //
    //    public function setEvent(DeliveryEventUid $event): void
    //    {
    //        $this->event = $event;
    //    }

    /** Пользовательские поля */
    public function getField(): ArrayCollection
    {
        return $this->field;
    }

    public function setField(ArrayCollection $field): void
    {
        $this->field = $field;
    }

    public function addField(Field\OrderDeliveryFieldDTO $field): void
    {
        if(!$this->field->contains($field))
        {
            $this->field->add($field);
        }
    }

    /**
     * Address.
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    /**
     * Pickup.
     */
    public function isPickup(): bool
    {
        return $this->pickup;
    }

    public function setPickup(bool $pickup): void
    {
        $this->pickup = $pickup;
    }


    //
    //    public function removeField(Field\OrderDeliveryFieldDTO $field): void
    //    {
    //        $this->field->removeElement($field);
    //    }
}
