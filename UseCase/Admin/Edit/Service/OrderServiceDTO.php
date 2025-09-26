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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\Service;

use BaksDev\Orders\Order\Entity\Services\OrderServiceInterface;
use BaksDev\Orders\Order\Type\OrderService\OrderServiceUid;
use BaksDev\Orders\Order\Type\OrderService\Period\ServicePeriodUid;
use BaksDev\Orders\Order\Type\OrderService\Service\ServiceUid;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Service\Price\OrderServicePriceDTO;
use BaksDev\Reference\Money\Type\Money;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderService */
final class OrderServiceDTO implements OrderServiceInterface
{
    /** Идентификатор продукта в заказе */
    #[Assert\Uuid]
    private OrderServiceUid $id;

    /** Идентификатор продукта в заказе */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ServiceUid $serv;

    #[Assert\NotBlank]
    private string $name;

    /** Стоимость */
    #[Assert\NotBlank]
    private Money $money;

    /** Дата */
    #[Assert\NotBlank]
    private ?DateTimeImmutable $date = null;

    private ServicePeriodUid|string|null $period = null;

    public OrderServicePriceDTO $price;

    public int $minPrice = 0;

    public function __construct()
    {
        $this->price = new OrderServicePriceDTO();
    }

    public function getId(): OrderServiceUid
    {
        return new OrderServiceUid($this->id);
    }

    public function setId(OrderServiceUid|string $id): self
    {
        $this->id = $id instanceof OrderServiceUid ? $id : new OrderServiceUid($id);
        return $this;
    }

    public function getServ(): ServiceUid
    {
        return $this->serv;
    }

    public function setServ(ServiceUid $serv): self
    {
        $this->serv = $serv;
        return $this;
    }

    /** Стоимость */
    public function getPrice(): OrderServicePriceDTO
    {
        return $this->price;
    }

    public function setPrice(OrderServicePriceDTO $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?DateTimeImmutable $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getPeriod(): ServicePeriodUid|null
    {
        return (true === is_string($this->period)) ? new ServicePeriodUid($this->period) : $this->period;
    }

    public function setPeriod(ServicePeriodUid|string|null $period): void
    {
        $this->period = (true === is_string($this->period)) ? new ServicePeriodUid($period) : $period;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function setMoney(Money|float|null $money): self
    {
        $this->money = $money instanceof Money ? $money : new Money($money);
        $this->price->setPrice($this->money);
        return $this;
    }

    public function getMinPrice(): int
    {
        return $this->minPrice;
    }

    public function setMinPrice(int $minPrice): void
    {
        $this->minPrice = $minPrice;
    }
}