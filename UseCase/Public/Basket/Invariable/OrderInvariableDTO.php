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
 *
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\UseCase\Public\Basket\Invariable;

use BaksDev\Orders\Order\Entity\Invariable\OrderInvariableInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderInvariable */
final class OrderInvariableDTO implements OrderInvariableInterface
{
    /**
     * Дата заказа
     */
    #[Assert\NotBlank]
    private ?DateTimeImmutable $created;

    /**
     * Идентификатор заказа
     */
    #[Assert\NotBlank]
    private string $number;

    /**
     * ID пользователя ответственного
     */
    #[Assert\Uuid]
    private UserUid|null $usr = null;

    /**
     * ID профиля ответственного
     */
    #[Assert\Uuid]
    private UserProfileUid|null $profile = null;


    public function __construct()
    {
        /** Генерируем идентификатор заказа */
        $this->number = number_format((microtime(true) * 100), 0, '.', '.');
        $this->created = new DateTimeImmutable();
    }

    /**
     * Created
     */
    public function getCreated(): DateTimeImmutable
    {
        if($this->created instanceof DateTimeImmutable)
        {
            return $this->created;
        }

        return new DateTimeImmutable();
    }

    /**
     * Usr
     */
    public function getUsr(): ?UserUid
    {
        return $this->usr;
    }

    public function setUsr(UserUid|string|null $usr): self
    {
        if(empty($usr))
        {
            $this->usr = null;
            return $this;
        }

        if(is_string($usr))
        {
            $usr = new UserUid($usr);
        }


        $this->usr = $usr;
        return $this;
    }

    /**
     * Profile
     */
    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }

    public function setProfile(UserProfileUid|string|null $profile): self
    {
        if(empty($profile))
        {
            $this->profile = null;
            return $this;
        }

        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        $this->profile = $profile;
        return $this;
    }


    /**
     * Number
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): OrderInvariableDTO
    {
        $this->number = $number;
        return $this;
    }
}
