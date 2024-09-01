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

declare(strict_types=1);

namespace BaksDev\Orders\Order\UseCase\Admin\Package;

use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User as UserEntity;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

//use BaksDev\Users\User\Entity\User;

/** @see OrderEvent */
final class PackageOrderDTO implements OrderEventInterface
{
    /** Идентификатор события */
    #[Assert\Uuid]
    private ?OrderEventUid $id = null;

    /** Коллекция продукции в заказе */
    #[Assert\Valid]
    private ArrayCollection $product;

    /** Постоянная величина */
    #[Assert\Valid]
    private readonly Invariable\PackageOrderInvariableDTO $invariable;

    /** Статус заказа */
    #[Assert\NotBlank]
    private OrderStatus $status;

    /** Пользовательские данные заказа */
    #[Assert\Valid]
    private User\OrderUserDTO $usr;


    /**
     * Склад назначения (Профиль пользователя)
     * @deprecated
     */
    #[Assert\Uuid]
    private ?UserProfileUid $profile = null;


    private UserUid $current;


    public function __construct(UserEntity|UserUid $user, UserProfileUid $profile)
    {
        $user = $user instanceof UserEntity ? $user->getId() : $user;

        $this->current = $user;
        $this->product = new ArrayCollection();

        $PackageOrderInvariable = new Invariable\PackageOrderInvariableDTO();
        $PackageOrderInvariable->setUsr($user);
        $PackageOrderInvariable->setProfile($profile);

        $this->invariable = $PackageOrderInvariable;

    }

    public function getEvent(): ?OrderEventUid
    {
        return $this->id;
    }


    /** Коллекция продукции в заказе */

    public function getProduct(): ArrayCollection
    {
        return $this->product;
    }

    public function setProduct(ArrayCollection $product): void
    {
        $this->product = $product;
    }

    public function addProduct(Products\PackageOrderProductDTO $product): void
    {
        if(!$this->product->contains($product))
        {
            $this->product->add($product);
        }
    }

    public function removeProduct(Products\PackageOrderProductDTO $product): void
    {
        $this->product->removeElement($product);
    }

    /** Пользовательские данные заказа */

    public function getUsr(): User\OrderUserDTO
    {

        if(!(new ReflectionProperty(self::class, 'usr'))->isInitialized($this))
        {
            $this->usr = new User\OrderUserDTO();
        }

        return $this->usr;
    }


    /**
     * Profile
     */
    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }

    public function setProfile(?UserProfileUid $profile): self
    {
        /** Присваиваем постоянную величину  */
        $PackageOrderInvariable = $this->getInvariable();
        $PackageOrderInvariable->setProfile($profile);

        $this->profile = $profile;

        return $this;
    }

    /**
     * Current
     */
    public function getCurrent(): UserUid
    {
        return $this->current;
    }

    /**
     * Status
     */
    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    /**
     * Invariable
     */
    public function getInvariable(): Invariable\PackageOrderInvariableDTO
    {
        return $this->invariable;
    }

}
