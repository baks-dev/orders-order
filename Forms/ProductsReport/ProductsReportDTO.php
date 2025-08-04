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

namespace BaksDev\Orders\Order\Forms\ProductsReport;

use DateTimeImmutable;

final class ProductsReportDTO
{
    private ?DateTimeImmutable $from = null;

    private ?DateTimeImmutable $to = null;

    private bool $all = false;

    /**
     * From
     */
    public function getFrom(): DateTimeImmutable
    {
        return $this->from ?: new DateTimeImmutable('now');
    }

    public function setFrom(DateTimeImmutable $from): self
    {
        $this->from = $from;
        return $this;
    }

    /**
     * To
     */
    public function getTo(): DateTimeImmutable
    {
        return $this->to ?: new DateTimeImmutable('now');
    }

    public function setTo(DateTimeImmutable $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function isAll(): bool
    {
        return $this->all;
    }

    public function setAll(bool $all): self
    {
        $this->all = $all;
        return $this;
    }
}