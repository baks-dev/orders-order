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

namespace BaksDev\Orders\Order\Entity\Invariable;

use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* OrderInvariable */

#[ORM\Entity]
#[ORM\Table(name: 'orders_invariable')]
class OrderInvariable extends EntityReadonly
{
    /**
     * Идентификатор Main
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OrderUid::TYPE)]
    private OrderUid $main;

    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\OneToOne(targetEntity: OrderEvent::class, inversedBy: 'invariable')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private OrderEvent $event;

    /**
     * Идентификатор заказа
     * TODO: unique: true, nullable: false
     */
    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    private ?string $number = null;

    /**
     * ID пользователя заказа
     */
    #[ORM\Column(type: UserUid::TYPE, nullable: true)]
    private ?UserUid $usr = null;

    /**
     * ID профиля заказа
     */
    #[ORM\Column(type: UserProfileUid::TYPE, nullable: true)]
    private ?UserProfileUid $profile = null;

    /**
     * Дата заказа
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $created;

    public function __construct(OrderEvent $event)
    {
        $this->event = $event;
        $this->main = $event->getMain();
        $this->created = new DateTimeImmutable();
    }

    public function __toString(): string
    {
        return (string) $this->main;
    }

    public function setEvent(OrderEvent $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }

    public function getUsr(): ?UserUid
    {
        return $this->usr;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OrderInvariableInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderInvariableInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}
