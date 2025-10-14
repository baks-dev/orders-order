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

namespace BaksDev\Orders\Order\UseCase\Public\Basket\Service;

use BaksDev\Orders\Order\Entity\Services\OrderServiceInterface;
use BaksDev\Orders\Order\Type\OrderService\OrderServiceUid;
use BaksDev\Orders\Order\Type\OrderService\Service\ServiceUid;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Service\Price\OrderServicePriceDTO;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Orders\Order\Type\OrderService\Period\ServicePeriodUid;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

final class BasketServiceDTO implements OrderServiceInterface
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

    private ?string $preview;

    /** Стоимость */
    #[Assert\NotBlank]
    private Money $money;

    private bool $selected = false;

    public function setSelected(bool $selected): self
    {
        $this->selected = $selected;
        return $this;
    }

    public function getSelected(): bool
    {
        return $this->selected === true;
    }

    /** Дата */
    private ?DateTimeImmutable $date = null;

    #[Assert\Uuid]
    private ?ServicePeriodUid $period = null;

    public OrderServicePriceDTO $price;

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

    public function getPeriod(): ?ServicePeriodUid
    {
        return $this->period;
    }

    public function setPeriod(?ServicePeriodUid $period): void
    {
        $this->period = $period;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function setMoney(Money $money): self
    {
        $this->money = $money;
        $this->price->setPrice($money);
        return $this;
    }

    public function getPreview(): ?string
    {
        return $this->preview;
    }

    public function setPreview(?string $preview): self
    {
        $this->preview = $preview;
        return $this;
    }

}