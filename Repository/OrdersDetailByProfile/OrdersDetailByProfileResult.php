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

namespace BaksDev\Orders\Order\Repository\OrdersDetailByProfile;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Payment\OrderPaymentUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints\Currency;

final readonly class OrdersDetailByProfileResult
{
    public function __construct(
        private string $order_id,
        private string $order_event,
        private string $order_number,
        private string $order_status,
        private string $order_data,
        private ?string $order_comment,
        private string $profile_avatar_name,
        private ?string $payment_id,
        private ?string $payment_name,
        private ?string $order_delivery_price,
        private ?string $order_delivery_currency,
        private ?string $delivery_name,
        private ?int $delivery_price,
        private ?string $delivery_geocode_longitude,
        private ?string $delivery_geocode_latitude,
        private ?string $delivery_geocode_address,
        private ?int $order_profile_discount,
        private ?string $order_profile,
        private ?string $profile_avatar_ext,
        private ?bool $profile_avatar_cdn,
    ) {}

    public function getOrderId(): OrderUid
    {
        return new OrderUid($this->order_id);
    }

    public function getOrderEvent(): OrderEventUid
    {
        return new OrderEventUid($this->order_event);
    }

    public function getOrderNumber(): string
    {
        return $this->order_number;
    }

    public function getOrderStatus(): OrderStatus
    {
        return new OrderStatus($this->order_status);
    }

    public function getOrderData(): string
    {
        return $this->order_data;
    }

    public function getOrderComment(): ?string
    {
        return $this->order_comment;
    }

    public function getProfileAvatarName(): string
    {
        return $this->profile_avatar_name;
    }

    public function getPaymentId(): ?OrderPaymentUid
    {
        return false === empty($this->payment_id) ? new OrderPaymentUid($this->payment_id) : null;
    }

    public function getPaymentName(): ?string
    {
        return $this->payment_name;
    }

    public function getOrderDeliveryPrice(): ?Money
    {
        return new Money($this->order_delivery_price);
    }

    public function getOrderDeliveryCurrency(): ?Currency
    {
        return new Currency($this->order_delivery_currency);
    }

    public function getDeliveryName(): ?string
    {
        return $this->delivery_name;
    }

    public function getDeliveryPrice(): ?Money
    {
        return new Money($this->delivery_price);
    }

    public function getDeliveryGeocodeLongitude(): ?GpsLongitude
    {
        return new GpsLongitude($this->delivery_geocode_longitude);
    }

    public function getDeliveryGeocodeLatitude(): ?GpsLatitude
    {
        return new GpsLatitude($this->delivery_geocode_latitude);
    }

    public function getDeliveryGeocodeAddress(): ?string
    {
        return $this->delivery_geocode_address;
    }

    public function getOrderProfileDiscount(): ?int
    {
        return $this->order_profile_discount;
    }

    public function getOrderProfile(): ?UserProfileUid
    {
        return false === empty($this->order_profile) ? new UserProfileUid($this->order_profile) : null;
    }

    public function getProfileAvatarExt(): ?string
    {
        return $this->profile_avatar_ext;
    }

    public function getProfileAvatarCdn(): bool
    {
        return true === $this->profile_avatar_cdn;
    }
}