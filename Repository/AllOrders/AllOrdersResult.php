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

namespace BaksDev\Orders\Order\Repository\AllOrders;

use BaksDev\Field\Pack\Contact\Type\ContactField;
use BaksDev\Field\Pack\Organization\Type\OrganizationField;
use BaksDev\Field\Pack\Phone\Type\PhoneField;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\OrderService\OrderServiceUid;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use DateTimeImmutable;

final  class AllOrdersResult
{

    public function __construct(
        private readonly string $order_id, //  "01986a30-80e8-7dc4-92db-007c0483d520"
        private readonly string $order_event, //  "01986a30-80e8-7dc4-92db-007c054183c9"
        private readonly ?string $order_number, //  "175.412.813.102"
        private readonly string $order_created, //  "2025-08-02 12:50:20"
        private readonly string $order_status, //  "new"

        private ?string $order_comment, //  null
        private readonly ?bool $order_danger, //  false

        private readonly ?string $stock_profile_username, //  "admin"
        private readonly ?string $stock_profile_location, //  null

        private readonly ?string $product_price,
        private readonly ?string $service_price,

        //  "[{
        //      "price": 495000,
        //      "total": 1,
        //      "product": "01986a30-80e9-7228-ae35-e26893974b5d",
        //      "main": "01986a30-80e9-7228-ae35-e26893974b5d",
        //      "offer": "01986a30-80e9-7228-ae35-e26893974b5d",
        //      "variation": "01986a30-80e9-7228-ae35-e26893974b5d"
        //      "modification": "01986a30-80e9-7228-ae35-e26893974b5d"
        //}]"
        private readonly ?string $order_currency, //  "rub"

        private readonly ?int $order_delivery_price, //  null
        private readonly ?string $order_delivery_currency, //  null

        private readonly ?string $delivery_name, //  "Самовывоз"
        private readonly ?string $delivery_date, //  "2025-08-05 00:00:00"
        private readonly ?int $delivery_price, //  0

        private readonly ?string $order_profile_discount, //  null
        private readonly ?string $account_email, //  null
        private readonly ?string $order_profile, //  "Пользователь"
        private readonly ?string $order_profile_username, //  "Пользователь"
        private readonly ?string $order_user,
        //  "[{"0": 1, "profile_name": "Контактный телефон", "profile_type": "phone_field", "profile_value": "+9 (878) 787-98-98"}]"
        private readonly ?bool $order_move, //  false
        private readonly ?bool $move_error, //  false
        private readonly ?bool $order_error, //  false

        private readonly string $modify, //  "2025-08-02 12:50:20"

        private readonly ?string $stocks,


        private readonly ?bool $is_other_project,
        private readonly ?string $project_profile_username,

        //  "[{
        //      "price": 495000,
        //      "total": 1,
        //      "product": "01986a30-80e9-7228-ae35-e26893974b5d",
        //      "main": "01986a30-80e9-7228-ae35-e26893974b5d",
        //      "offer": "01986a30-80e9-7228-ae35-e26893974b5d",
        //      "variation": "01986a30-80e9-7228-ae35-e26893974b5d"
        //      "modification": "01986a30-80e9-7228-ae35-e26893974b5d"
        //}]"
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
        return $this->order_number ?? 'Не указан';
    }

    public function getOrderCreated(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->order_created);
    }

    public function getOrderStatus(): OrderStatus
    {
        return new OrderStatus($this->order_status);
    }


    public function orderStatusEquals(mixed $status): bool
    {
        return new OrderStatus($this->order_status)->equals($status);
    }

    public function getOrderComment(): ?string
    {
        return $this->order_comment;
    }

    public function getOrderDanger(): ?bool
    {
        if(empty($this->order_number))
        {
            return true;
        }
        
        if(
            class_exists(BaksDevProductsStocksBundle::class)
            && $this->getOrderStatus()->equals(OrderStatusNew::class)
        )
        {
            foreach($this->getProductPrice() as $product)
            {
                $stock = array_filter(json_decode($this->stocks, false, 512, JSON_THROW_ON_ERROR),
                    static function($element) use ($product) {
                        return $element->main === $product->main
                            && $element->offer === $product->offer
                            && $element->variation === $product->variation
                            && $element->modification === $product->modification;
                    });

                $total = $product->total;

                foreach($stock as $item)
                {
                    $total -= ($item->total - $item->reserve);
                }

                if($total > 0)
                {
                    $this->order_comment = 'Отсутствует необходимое количество на складе';

                    return true;
                }
            }
        }

        return $this->order_danger;
    }

    public function getStockProfileUsername(): ?string
    {
        return $this->stock_profile_username;
    }

    public function getStockProfileLocation(): ?string
    {
        return $this->stock_profile_location;
    }

    /** Возвращает стоимость количество продукции */
    public function getProductPrice(): array|false
    {
        if(empty($this->product_price))
        {
            return false;
        }

        if(false === json_validate($this->product_price))
        {
            return false;
        }

        $items = json_decode($this->product_price, false, 512, JSON_THROW_ON_ERROR);

        // Обновляем поля price и product
        foreach($items as $item)
        {
            $item->price = new Money($item->price, true);
            $item->product = new OrderProductUid($item->product);
        }

        return $items;
    }

    /** Возвращает общее количество товаров в заказе */
    public function getProductTotal(): int|false
    {
        if(empty($this->product_price))
        {
            return false;
        }

        if(false === json_validate($this->product_price))
        {
            return false;
        }

        $items = json_decode($this->product_price, false, 512, JSON_THROW_ON_ERROR);

        $total = 0;

        // Обновляем поля price и product
        foreach($items as $item)
        {
            $total += $item->total;
        }

        return $total;
    }

    public function getServicePrice(): array|false
    {
        if(empty($this->service_price))
        {
            return false;
        }

        if(false === json_validate($this->service_price))
        {
            return false;
        }

        $items = json_decode($this->service_price, false, 512, JSON_THROW_ON_ERROR);

        // Обновляем поля price и product
        foreach($items as $item)
        {
            $item->price = new Money($item->price, true);
            $item->service = new OrderServiceUid($item->service);
            $item->currency = new Currency($item->currency);
        }

        return $items;
    }

    public function getAllServicePrice(): Money
    {
        $totalPrice = new Money(0);

        if(false === $this->getServicePrice())
        {
            return new Money(0);
        }

        foreach($this->getServicePrice() as $item)
        {
            $totalPrice->add($item->price);
        }

        return $totalPrice;
    }

    public function getAllProductPrice(): Money
    {
        $totalPrice = new Money(0);

        if(false === $this->getProductPrice())
        {
            return new Money(0);
        }

        foreach($this->getProductPrice() as $item)
        {
            if(empty($item->total))
            {
                continue;
            }

            $multiplication = $item->price->multiplication($item->total);
            $totalPrice->add($multiplication);
        }

        return $totalPrice;
    }


    public function getOrderCurrency(): Currency
    {
        return new Currency($this->order_currency);
    }

    public function getOrderDeliveryPrice(): Money|false
    {
        return $this->order_delivery_price ? new Money($this->order_delivery_price, true) : false;
    }

    public function getOrderDeliveryCurrency(): Currency
    {
        return new Currency($this->order_delivery_currency);
    }

    public function getDeliveryName(): ?string
    {
        return $this->delivery_name;
    }

    public function getDeliveryDate(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->delivery_date ?: 'now');
    }

    public function getDeliveryPrice(): Money|false
    {
        return $this->delivery_price ? new Money($this->delivery_price, true) : false;
    }

    public function getAccountEmail(): ?string
    {
        return $this->account_email;
    }

    public function getOrderProfile(): ?string
    {
        return $this->order_profile;
    }

    public function getOrderProfileDiscount(): ?string
    {
        return $this->order_profile_discount;
    }

    private function getOrderUser(): array|false
    {
        if(empty($this->order_user))
        {
            return false;
        }

        if(false === json_validate($this->order_user))
        {
            return false;
        }

        return json_decode($this->order_user, false, 512, JSON_THROW_ON_ERROR);
    }

    public function getClientUsername(): ?string
    {
        return $this->order_profile_username;
    }



    public function getOrganizationName(): ?object
    {
        /** Пробуем определить название организации */
        $filter = array_filter($this->getOrderUser(), static function(object $element) {
            return $element->profile_type === OrganizationField::TYPE;
        });

        return current($filter) ?: null;
    }

    public function getClientName(): array|false
    {
        $filter = array_filter($this->getOrderUser(), static function(object $element) {
            return $element->profile_type === ContactField::TYPE;
        });

        /** Возвращаем массив, т.к. может быть несколько полей для заполнения */

        return empty($filter) ? false : $filter;
    }

    public function getClientPhone(): ?object
    {
        $filter = array_filter($this->getOrderUser(), static function(object $element) {
            return $element->profile_type === PhoneField::TYPE;
        });

        return current($filter) ?: null;
    }

    public function getOrderMove(): ?bool
    {
        return $this->order_move;
    }

    public function getMoveError(): ?bool
    {
        return $this->move_error;
    }

    public function getOrderError(): ?bool
    {
        return $this->order_error;
    }

    public function getDateModify(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->modify);
    }

    public function getIsOtherProject(): ?bool
    {
        return $this->is_other_project === true;
    }

    public function getProjectProfileUsername(): ?string
    {
        return $this->project_profile_username;
    }


}