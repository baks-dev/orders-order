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

namespace BaksDev\Orders\Order\Repository\AllServicesOrdersReport;

use BaksDev\Reference\Money\Type\Money;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class AllServicesOrdersReportResult
{
    public function __construct(
        private readonly string $order_number,
        private readonly string $order_posting,
        private readonly string $mod_date,

        private readonly ?int $service_price,
        private readonly ?int $order_service_price,
        private readonly ?string $service_name,

        private readonly ?bool $danger,

        private readonly ?string $comment,
    ) {}

    public function getDate(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->mod_date);
    }

    public function getOrderPosting(): string
    {
        return $this->order_posting;
    }

    public function getOrderNumber(): string
    {
        return $this->order_number;
    }

    public function getOrderServicePrice(): Money
    {
        $order_service_price = new Money($this->order_service_price, true);

        return $order_service_price;
    }

    public function getServicePrice(): Money
    {
        $service_price = new Money($this->service_price, true);

        return $service_price;
    }


    public function getServiceName(): ?string
    {
        return $this->service_name;
    }


    public function isDanger(): ?bool
    {
        return $this->danger === true;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}