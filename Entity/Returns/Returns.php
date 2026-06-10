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

namespace BaksDev\Orders\Order\Entity\Returns;

use BaksDev\Orders\Order\Entity\Returns\Event\ReturnsEvent;
use BaksDev\Orders\Order\Type\Returns\Event\ReturnsEventUid;
use BaksDev\Orders\Order\Type\Returns\id\ReturnsUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/* Returns */

#[ORM\Entity]
#[ORM\Table(name: 'returns')]
class Returns
{
    /**
     * Идентификатор сущности
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: ReturnsUid::TYPE)]
    private ReturnsUid $id;

    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ReturnsEventUid::TYPE, unique: true)]
    private ReturnsEventUid $event;

    public function __construct()
    {
        $this->id = new ReturnsUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    /**
     * Идентификатор События
     */
    public function getEvent(): ReturnsEventUid
    {
        return $this->event;
    }

    public function setEvent(ReturnsEventUid|ReturnsEvent $event): void
    {
        $this->event = $event instanceof ReturnsEvent ? $event->getId() : $event;
    }

    /**
     * Идентификатор
     */
    public function getId(): ReturnsUid
    {
        return $this->id;
    }
}