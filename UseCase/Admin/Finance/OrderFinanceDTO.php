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

namespace BaksDev\Orders\Order\UseCase\Admin\Finance;

use BaksDev\Orders\Order\Entity\Event\Finance\OrderFinanceInterface;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderFinance */
final class OrderFinanceDTO implements OrderFinanceInterface
{
    /**
     * Идентификатор Main
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private OrderUid $main;

    /**
     * Выплаты по заказу
     */
    private Money $value;

    public function getMain(): OrderUid
    {
        return $this->main;
    }

    public function setMain(OrderUid $main): self
    {
        $this->main = $main;
        return $this;
    }

    public function getValue(): Money
    {
        return $this->value;
    }

    public function setValue(Money $value): self
    {
        $this->value = $value;
        return $this;
    }
}