<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Admin\Status\Modify;

use BaksDev\Core\Type\Modify\Modify\ModifyActionUpdate;
use BaksDev\Core\Type\Modify\ModifyAction;
use BaksDev\Orders\Order\Entity\Modify\OrderModifyInterface;
use BaksDev\Products\Stocks\Entity\Modify\ProductStockModify;
use BaksDev\Users\User\Type\Id\UserUid;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ProductStockModify */
final class ModifyDTO implements OrderModifyInterface
{
    /**
     * ID пользователя
     * Присваивается в случае, если статус асинхронно меняется складской заявкой
     */
    private readonly ?UserUid $usr;
    /**
     * Модификатор
     */
    #[Assert\NotBlank]
    private ModifyAction $action;

    public function __construct()
    {
        $this->action = new ModifyAction(ModifyActionUpdate::class);
    }

    public function getAction(): ModifyAction
    {
        return $this->action;
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
        if(!(new ReflectionProperty(self::class, 'usr'))->isInitialized($this))
        {
            $this->usr = $usr;
        }

        return $this;
    }
}

