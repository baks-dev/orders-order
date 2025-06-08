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
 *
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Repository\OrderDetail;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/** @see OrderDetailRepository */
#[Exclude]
class OrderDetailResult
{

    public function __construct(
        private string $order_id,
        private string $order_event,
        private string $order_number,
        private string $order_status,
        private string $order_data,
        private string|null $order_comment,
        private string $payment_id,
        private string $payment_name,
        private string $order_products,
        private int|null $order_delivery_price,
        private string|null $order_delivery_currency,
        private string $delivery_name,
        private int $delivery_price,
        private string|null $delivery_geocode_longitude,
        private string|null $delivery_geocode_latitude,
        private string|null $delivery_geocode_address,
        private string|null $order_profile_discount,
        private string $order_profile,
        private string $profile_avatar_name,
        private string|null $profile_avatar_ext,
        private string|null $profile_avatar_cdn,
        private string $order_user,
    ) {}

    public function getOrderId(): string
    {
        return $this->order_id;
    }

    public function getOrderEvent(): string
    {
        return $this->order_event;
    }

    public function getOrderNumber(): string
    {
        return $this->order_number;
    }

    public function getOrderStatus(): string
    {
        return $this->order_status;
    }

    public function getOrderData(): string
    {
        return $this->order_data;
    }

    public function getOrderComment(): ?string
    {
        return $this->order_comment;
    }

    public function getPaymentId(): string
    {
        return $this->payment_id;
    }

    public function getPaymentName(): string
    {
        return $this->payment_name;
    }

    public function getOrderProducts(): array|null
    {
        if(is_null($this->order_products))
        {
            return null;
        }

        if(false === json_validate($this->order_products))
        {
            return null;
        }

        $products = json_decode($this->order_products, true, 512, JSON_THROW_ON_ERROR);

        if(null === current($products))
        {
            return null;
        }

        return $products;
    }

    public function getOrderDeliveryPrice(): ?int
    {
        return $this->order_delivery_price;
    }

    public function getOrderDeliveryCurrency(): ?string
    {
        return $this->order_delivery_currency;
    }

    public function getDeliveryName(): string
    {
        return $this->delivery_name;
    }

    public function getDeliveryPrice(): int
    {
        return $this->delivery_price;
    }

    public function getDeliveryGeocodeLongitude(): ?string
    {
        return $this->delivery_geocode_longitude;
    }

    public function getDeliveryGeocodeLatitude(): ?string
    {
        return $this->delivery_geocode_latitude;
    }

    public function getDeliveryGeocodeAddress(): ?string
    {
        return $this->delivery_geocode_address;
    }

    public function getOrderProfileDiscount(): ?string
    {
        return $this->order_profile_discount;
    }

    public function getOrderProfile(): string
    {
        return $this->order_profile;
    }

    public function getProfileAvatarName(): string
    {
        return $this->profile_avatar_name;
    }

    public function getProfileAvatarExt(): ?string
    {
        return $this->profile_avatar_ext;
    }

    public function getProfileAvatarCdn(): ?string
    {
        return $this->profile_avatar_cdn;
    }

    public function getOrderUser(): string
    {
        return $this->order_user;
    }

}