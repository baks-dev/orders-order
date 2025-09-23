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

namespace BaksDev\Orders\Order\Type\OrderService\Period;

use BaksDev\Core\Type\UidType\Uid;
use Symfony\Component\Uid\AbstractUid;

final class ServicePeriodUid extends Uid
{
    /** Тестовый идентификатор */
    public const string TEST = '581bc022-84d1-7a74-b2ee-606f0ffa908b';

    public const string TYPE = 'service_period';

    private array|string|null $params;

    public function __construct(
        AbstractUid|self|string|null $value = null,
        array|string|null $params = null
    )
    {
        parent::__construct($value);
        $this->params = $params;
    }

    public function getParams(?string $key = null): mixed
    {
        if(empty($this->params))
        {
            return null;
        }

        if(is_array($this->params))
        {
            if($key)
            {
                return $this->params[$key];
            }

            return $this->params;
        }

        if(false === json_validate($this->params))
        {
            return $this->params;
        }

        return json_decode($this->params, true, 512, JSON_THROW_ON_ERROR);
    }

    public function setParams(array|string|null $params): void
    {
        $this->params = $params;
    }

}