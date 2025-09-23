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

namespace BaksDev\Orders\Order\Repository\Services\OneServiceById;

use BaksDev\Orders\Order\Type\OrderService\Service\ServiceUid;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Services\Type\Event\ServiceEventUid;
use DateTimeImmutable;

final readonly class OneServiceByIdResult
{
    public function __construct(
        private string $id,
        private string $event,
        private int $price,
        private string $currency,
        private string $name,
        private ?string $preview,
        private string $frm,
        private string $upto,
    ) {}

    public function getId(): ServiceUid
    {
        return new ServiceUid($this->id);
    }

    public function getEvent(): ServiceEventUid
    {
        return new ServiceEventUid($this->event);
    }

    public function getPrice(): Money
    {
        return new Money($this->price, true);
    }

    public function getCurrency(): Currency
    {
        return new Currency($this->currency);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPreview(): ?string
    {
        return $this->preview;
    }

    public function getFrom(): string
    {
        return new DateTimeImmutable($this->frm);
    }

    public function getTo(): string
    {
        return new DateTimeImmutable($this->upto);
    }
}


