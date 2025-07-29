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

namespace BaksDev\Orders\Order\UseCase\Admin\New\Invariable;

use BaksDev\Orders\Order\Entity\Invariable\OrderInvariableInterface;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderInvariableDTO */
final class NewOrderInvariableDTO implements OrderInvariableInterface
{
    /**
     * Дата заказа
     */
    #[Assert\NotBlank]
    private readonly DateTimeImmutable $created;

    /**
     * Идентификатор заказа
     */
    private string $number;


    /**
     * ID пользователя заказа
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ?UserUid $usr = null;

    /**
     * ID профиля заказа
     */
    #[Assert\IsNull]
    private readonly null $profile;


    public function __construct()
    {
        $this->created = new DateTimeImmutable();
        $this->profile = null;
        $this->number = number_format((microtime(true) * 100), 0, '.', '.');
    }

    /**
     * Created
     */
    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }


    /**
     * Usr
     */
    public function getUsr(): ?UserUid
    {
        return $this->usr;
    }

    public function setUsr(?UserUid $usr): self
    {
        $this->usr = $usr;
        return $this;
    }


    /**
     * Profile
     */
    public function getProfile(): null
    {
        return $this->profile;
    }

    /**
     * Number
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        if(empty($number))
        {
            return $this;
        }

        $this->number = $number;

        return $this;
    }
}
