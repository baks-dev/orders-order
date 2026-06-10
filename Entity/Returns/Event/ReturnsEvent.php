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

namespace BaksDev\Orders\Order\Entity\Returns\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Entity\EntityState;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Core\Type\Modify\ModifyAction;
use BaksDev\Orders\Order\Entity\Returns\Event\Invariable\ReturnsInvariable;
use BaksDev\Orders\Order\Entity\Returns\Event\Modify\ReturnsModify;
use BaksDev\Orders\Order\Type\Returns\Event\ReturnsEventUid;
use BaksDev\Orders\Order\Type\Returns\id\ReturnsUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;


/* ReturnsEvent */

#[ORM\Entity]
#[ORM\Table(name: 'returns_event')]
class ReturnsEvent extends EntityEvent
{
    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: ReturnsEventUid::TYPE)]
    private ReturnsEventUid $id;

    /**
     * Идентификатор Returns
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ReturnsUid::TYPE, nullable: false)]
    private ?ReturnsUid $main = null;

    // /** ReturnsInvariable */
    #[ORM\OneToOne(targetEntity: ReturnsInvariable::class, mappedBy: 'event', cascade: ['all'])]
    private ?ReturnsInvariable $invariable = null;


    /** One To One */
    //#[ORM\OneToOne(mappedBy: 'event', targetEntity: ReturnsLogo::class, cascade: ['all'])]
    //private ?ReturnsOne $one = null;

    /**
     * Модификатор
     */
    #[ORM\OneToOne(targetEntity: ReturnsModify::class, mappedBy: 'event', cascade: ['all'])]
    private ReturnsModify $modify;

    /**
     * Переводы
     */
    //#[ORM\OneToMany(mappedBy: 'event', targetEntity: ReturnsTrans::class, cascade: ['all'])]
    //private Collection $translate;


    public function __construct()
    {
        $this->id = new ReturnsEventUid();
        $this->modify = new ReturnsModify($this);

    }

    /**
     * Идентификатор События
     */

    public function __clone()
    {
        $this->id = clone new ReturnsEventUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getMain(): ?ReturnsUid
    {
        return $this->main;
    }

    /**
     * Идентификатор Returns
     */
    public function setMain(ReturnsUid|Returns $main): void
    {
        $this->main = $main instanceof Returns ? $main->getId() : $main;
    }

    public function getId(): ReturnsEventUid
    {
        return $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof ReturnsEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof ReturnsEventInterface)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    //	public function isModifyActionEquals(ModifyActionEnum $action) : bool
    //	{
    //		return $this->modify->equals($action);
    //	}

    //	public function getUploadClass() : ReturnsImage
    //	{
    //		return $this->image ?: $this->image = new ReturnsImage($this);
    //	}

    //	public function getNameByLocale(Locale $locale) : ?string
    //	{
    //		$name = null;
    //		
    //		/** @var ReturnsTrans $trans */
    //		foreach($this->translate as $trans)
    //		{
    //			if($name = $trans->name($locale))
    //			{
    //				break;
    //			}
    //		}
    //		
    //		return $name;
    //	}
}