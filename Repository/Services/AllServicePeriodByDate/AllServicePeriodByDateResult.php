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

namespace BaksDev\Orders\Order\Repository\Services\AllServicePeriodByDate;

use BaksDev\Orders\Order\Type\OrderService\Period\ServicePeriodUid;
use DateTimeImmutable;

final readonly class AllServicePeriodByDateResult
{
    public function __construct(
        private string $period_id,
        private string $frm,
        private string $upto,
        private string|null $orders_service_date,
        private bool $order_service_active,
    ) {}

    public function getPeriodId(): ServicePeriodUid
    {
        return new ServicePeriodUid($this->period_id);
    }

    public function getFrom(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->frm);
    }

    public function getTo(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->upto);
    }

    public function getOrdersServiceDate(): ?DateTimeImmutable
    {
        return false === is_null($this->orders_service_date) ? new DateTimeImmutable($this->orders_service_date) : null;
    }

    public function isOrderServiceActive(): bool
    {
        return $this->order_service_active;
    }
}