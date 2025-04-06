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

namespace BaksDev\Orders\Order\Type\Status\OrderStatus;

use BaksDev\Orders\Order\Type\Status\OrderStatus;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class OrderStatusCollection
{
    public function __construct(
        #[AutowireIterator('baks.order.status', defaultPriorityMethod: 'sort')] private iterable $status
    ) {}

    /** Возвращает массив из значений OrderStatus */
    public function cases(): array
    {
        $case = null;

        foreach($this->status as $key => $status)
        {
            $case[$status::priority().$key] = new $status();
        }

        ksort($case);

        return $case;
    }

    /** Метод возвращает класс статуса заказа  */
    public function from(string $name): OrderStatus
    {
        /** @var OrderStatusInterface $status */
        foreach($this->status as $status)
        {
            if($status::STATUS === $name)
            {
                return new OrderStatus(new $status());
            }
        }

        throw new InvalidArgumentException(sprintf('OrderStatus not found by name %s', $name));
    }
}
