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

namespace BaksDev\Orders\Order\Messenger;

use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class OrderMessage
{
    /** Идентификатор заказа */
    private string $id;

    /** Идентификатор события заказа */
    private string $event;

    /** Идентификатор предыдущего события заказа */
    private string|false $last;

    /** Профиль предыдущего события заказа */
    private string|false $lastProfile;

    public function __construct(
        OrderUid $id, OrderEventUid $event,
        ?OrderEventUid $last = null,
        ?UserProfileUid $lastProfile = null
    )
    {
        $this->id = (string) $id;
        $this->event = (string) $event;
        $this->last = $last ? (string) $last : false;
        $this->lastProfile = $lastProfile ? (string) $lastProfile : false;
    }


    /** Идентификатор заказа */
    public function getId(): OrderUid
    {
        return new OrderUid($this->id);
    }


    /** Идентификатор события заказа */
    public function getEvent(): OrderEventUid
    {
        return new OrderEventUid($this->event);
    }


    /** Идентификатор предыдущего события */
    public function getLast(): OrderEventUid|false
    {
        return $this->last ? new OrderEventUid($this->last) : false;
    }


    /** Профиль предыдущего события заказа */
    public function getLastProfile(): UserProfileUid|false
    {
        return $this->lastProfile ? new UserProfileUid($this->lastProfile) : false;
    }
}
