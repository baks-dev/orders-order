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

namespace BaksDev\Orders\Order\Type\Status\OrderStatus\Collection;

use BaksDev\Orders\Order\Security\RoleOrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusInterface;
use BaksDev\Users\Profile\Group\Security\RoleInterface;
use BaksDev\Users\Profile\Group\Security\VoterInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Unpaid «В ожидании оплаты»
 */
#[AutoconfigureTag('baks.order.status')]
#[AutoconfigureTag('baks.security.voter')]
class OrderStatusUnpaid implements OrderStatusInterface, VoterInterface
{
    public const string STATUS = 'unpaid';

    private static int $sort = 120;

    private static string $color = '#6C757D';

    /** Возвращает значение (value) */
    public function getValue(): string
    {
        return self::STATUS;
    }

    /** Сортировка */
    public static function priority(): int
    {
        return self::$sort;
    }

    /** Цвет */
    public static function color(): string
    {
        return self::$color;
    }

    /** Правило доступа */
    public static function getVoter(): string
    {
        return RoleOrderStatus::ROLE.'_'.mb_strtoupper(self::STATUS);
    }

    public function equals(RoleInterface $role): bool
    {
        return RoleOrderStatus::ROLE === $role->getRole();
    }
}
