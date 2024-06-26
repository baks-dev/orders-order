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

namespace BaksDev\Orders\Order\UseCase\Admin\New\User\Delivery\Field;

use BaksDev\Delivery\Type\Field\DeliveryFieldUid;
use BaksDev\Orders\Order\Entity\User\Delivery\Field\OrderDeliveryFieldInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderDeliveryFieldDTO implements OrderDeliveryFieldInterface
{
    /** Самовывоз */
    private ?string $call = null;

    /** Идентификатор пользовательского поля в способе доставки */
    #[Assert\NotBlank]
    private DeliveryFieldUid $field;

    /** Заполненное значение */
    #[Assert\Valid]
    private ?string $value = null;

    /** Идентификатор пользовательского поля в способе оплаты */

    public function getField(): DeliveryFieldUid
    {
        return $this->field;
    }

    public function setField(DeliveryFieldUid $field): void
    {
        $this->field = $field;
    }


    /** Заполненное значение */

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Call
     */
    public function getCall(): ?string
    {
        return $this->value;
    }

    public function setCall(?string $call): self
    {
        if($call)
        {
            $this->value = $call;
        }

        return $this;
    }




    //    /**
    //     * Region
    //     */
    //    public function getRegion(): ?Region\ContactRegionFieldDTO
    //    {
    //        return $this->region;
    //    }
    //
    //    public function setRegion(?Region\ContactRegionFieldDTO $region): self
    //    {
    //        $this->region = $region;
    //        return $this;
    //    }


}
