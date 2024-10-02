<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Entity\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Type\Crypt\Crypt;
use BaksDev\Orders\Order\Entity\Const\OrderConst;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

// Event

#[ORM\Entity]
#[ORM\Table(name: 'orders_event')]
#[ORM\Index(columns: ['status'])]
#[ORM\Index(columns: ['created'])]
#[ORM\Index(columns: ['profile'])]
class OrderEvent extends EntityEvent
{
    public const TABLE = 'orders_event';

    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OrderEventUid::TYPE)]
    private OrderEventUid $id;

    /** ID заказа */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: OrderUid::TYPE)]
    private ?OrderUid $orders = null;

    /** Товары в заказе */
    #[Assert\Count(min: 1)]
    #[ORM\OneToMany(targetEntity: OrderProduct::class, mappedBy: 'event', cascade: ['all'])]
    private Collection $product;

    /**
     * Постоянная величина
     */
    #[ORM\OneToOne(targetEntity: OrderInvariable::class, mappedBy: 'event', cascade: ['all'])]
    private ?OrderInvariable $invariable = null;

    /** Дата заказа */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $created;

    /** Статус заказа */
    #[Assert\NotBlank]
    #[ORM\Column(type: OrderStatus::TYPE)]
    private OrderStatus $status;

    /**
     * Ответственный
     * @deprecated переносится в invariable
     */
    #[ORM\Column(type: UserProfileUid::TYPE, nullable: true)]
    private ?UserProfileUid $profile = null;


    /** Модификатор */
    #[ORM\OneToOne(targetEntity: OrderModify::class, mappedBy: 'event', cascade: ['all'])]
    private OrderModify $modify;

    /** Пользователь (Клиент) */
    #[ORM\OneToOne(targetEntity: OrderUser::class, mappedBy: 'event', cascade: ['all'])]
    private OrderUser $usr;

    /** Комментарий к заказу */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function __construct()
    {
        $this->id = new OrderEventUid();
        $this->modify = new OrderModify($this);
        $this->created = new DateTimeImmutable();
        $this->status = new OrderStatus(new OrderStatus\OrderStatusNew());
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): OrderEventUid
    {
        return $this->id;
    }

    public function getMain(): ?OrderUid
    {
        return $this->orders;
    }

    public function setMain(OrderUid|Order $order): void
    {
        $this->orders = $order instanceof Order ? $order->getId() : $order;
    }


    /**
     * @deprecated  используйте метод isStatusEquals
     * @see self::isStatusEquals
     */
    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function isStatusEquals(mixed $status): bool
    {
        return $this->status->equals($status);
    }

    public function getOrderNumber(): ?string
    {
        return $this->invariable?->getNumber();
    }

    public function getOrderProfile(): ?UserProfileUid
    {
        return $this->invariable?->getProfile();
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OrderEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getProduct(): Collection
    {
        return $this->product;
    }

    /**
     * Users.
     */
    public function getDelivery(): ?OrderDelivery
    {
        return $this->usr->getDelivery();
    }
}
