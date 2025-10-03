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

namespace BaksDev\Orders\Order\Forms\Package;

use BaksDev\Orders\Order\Forms\Package\Orders\PackageOrdersOrderDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

final class PackageOrdersDTO
{
    private ArrayCollection $orders;

    private ArrayCollection $products;

    /**
     * ID профиля заказа
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ?UserProfileUid $profile = null;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getOrders(): ArrayCollection
    {
        return $this->orders;
    }

    public function setOrders(ArrayCollection $orders): self
    {
        $this->orders = $orders;
        return $this;
    }

    public function addOrders(PackageOrdersOrderDTO $order): self
    {
        $filter = $this->orders->filter(function(PackageOrdersOrderDTO $element) use ($order) {
            return $element->getId()->equals($order->getId());
        });

        if($filter->isEmpty())
        {
            $this->orders->add($order);
        }

        return $this;
    }

    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }

    public function setProfile(?UserProfileUid $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    public function getProducts(): ArrayCollection
    {
        return $this->products;
    }

    public function setProducts(ArrayCollection $products): self
    {
        $this->products = $products;
        return $this;
    }


}