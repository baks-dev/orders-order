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

namespace BaksDev\Orders\Order\Entity\Services;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Services\Price\OrderServicePrice;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\OrderService\OrderServiceUid;
use BaksDev\Orders\Order\Type\OrderService\Period\ServicePeriodUid;
use BaksDev\Orders\Order\Type\OrderService\Service\ServiceUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'orders_service')]
class OrderService extends EntityEvent
{
    /** ID */
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OrderServiceUid::TYPE, nullable: false)]
    private OrderServiceUid $id;

    /** Связь на событие */
    #[ORM\ManyToOne(targetEntity: OrderEvent::class, inversedBy: 'service')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id', nullable: false)]
    private OrderEvent $event;

    /** Стоимость покупки услуги */
    #[ORM\OneToOne(targetEntity: OrderServicePrice::class, mappedBy: 'serv', cascade: ['all'], fetch: 'EAGER')]
    private OrderServicePrice $price;

    /** Идентификатор услуги */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    #[ORM\Column(type: ServiceUid::TYPE, nullable: false)]
    private ServiceUid $serv;

    /** Идентификатор периода услуги */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    #[ORM\Column(type: ServicePeriodUid::TYPE, nullable: false)]
    private ServicePeriodUid $period;

    /** Дата услуги */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $date;

    public function __construct(OrderEvent $event)
    {
        $this->id = new OrderServiceUid();
        $this->event = $event;
    }

    public function __clone(): void
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    /**
     * Id
     */
    public function getId(): OrderServiceUid
    {
        return $this->id;
    }

    /**
     * Event
     */
    public function getEvent(): OrderEventUid
    {
        return $this->event->getId();
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OrderServiceInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OrderServiceInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}