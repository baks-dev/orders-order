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
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Repository\AllOrders\Tests;

use BaksDev\Orders\Order\Repository\AllOrders\AllOrdersCTERepository;
use BaksDev\Orders\Order\Repository\AllOrders\AllOrdersInterface;
use BaksDev\Orders\Order\Repository\AllOrders\AllOrdersRepository;
use BaksDev\Orders\Order\Repository\AllOrders\AllOrdersResult;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[When(env: 'test')]
class AllOrdersRepositoryDebugTest extends KernelTestCase
{
    public function testRepository(): void
    {
        // @TODO вернуть при релизе
        self::assertTrue(true);
        return;

        /** Бросаем событие консольной команды */
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        //        $profile = $_SERVER['TEST_PROFILE'];
        //        $profile = '0196e4cf-fa40-79f1-aae6-70cfc444231a';
        $profile = '0194cc50-72cf-7371-86c4-6d80817957d1'; // white-sign


        /**
         * Тестируем запрос на ВСЕХ статусах с пагинатором и cte и сравниваем результат
         */

        /** @var OrderStatus $status */
        foreach(OrderStatus::cases() as $status)
        {
            $this->compare($status, $profile);
        }

        self::assertTrue(true);
    }

    private function compare(OrderStatus $status, string $profile)
    {
        $orders = null;

        /** @var AllOrdersInterface $AllOrdersRepository */
        $AllOrdersRepository = self::getContainer()->get(AllOrdersRepository::class);

        $start = hrtime(true);
        $paginator = $AllOrdersRepository
            ->setLimit(24)
            ->status($status)
            ->forProfile(new UserProfileUid($profile))
            ->findPaginator()
            ->getData();

        $end = hrtime(true);
        $durationMs = ($end - $start) / 1e+6;
        $durationSec = $durationMs / 1000;
        $paginatorTime = number_format($durationSec, 3).' sec';

        if(true === empty($paginator))
        {
            self::assertTrue(true);
            echo sprintf('%s не найдены заказы в статусе %s у профиля %s %s %s', PHP_EOL, $status, $profile, self::class, PHP_EOL);
            return;
        }


        /** @var AllOrdersCTERepository $AllOrdersCTERepository */
        $AllOrdersCTERepository = self::getContainer()->get(AllOrdersCTERepository::class);

        $start = hrtime(true);
        $cte = $AllOrdersCTERepository
            ->setLimit(24)
            ->status($status)
            ->forProfile(new UserProfileUid($profile))
            ->findPaginator()
            ->getData();

        $end = hrtime(true);

        if(true === empty($cte))
        {
            self::assertTrue(true);
            echo sprintf('%s не найдены заказы в статусе %s у профиля %s %s %s', PHP_EOL, $status, $profile, self::class, PHP_EOL);
            return;
        }

        $durationMs = ($end - $start) / 1e+6;
        $durationSec = $durationMs / 1000;
        $cteTime = number_format($durationSec, 3).' sec';

        /**
         * Форматируем результат
         */

        /**
         * Paginator
         *
         * @var AllOrdersResult $ord
         */
        foreach($paginator as $ord)
        {
            $orders['pag'][] = self::format($ord);
        }

        /**
         * CTE
         *
         * @var AllOrdersResult $ord
         */
        foreach($cte as $ord)
        {
            $orders['cte'][] = self::format($ord);
        }

        dump('============================');
        dump('========= status ===========');
        dump($status);

        dump('========= orders ===========');
        dump($orders);

        dump('========= equal ===========');

        $compare = $orders['pag'] === $orders['cte'];
        dump($compare);

        dump('========= diff ===========');
        dump(array_diff($orders['pag'], $orders['cte']));

        dump('========= time ===========');
        dump('pag: '.$paginatorTime);
        dump('cte: '.$cteTime);

        self::assertTrue($compare);
    }

    private static function format(AllOrdersResult $ord): string
    {
        return sprintf('%s - %s - id: %s - event: %s - d: %s - m: %s',
            $ord->getOrderStatus(),
            $ord->getOrderNumber(),
            $ord->getOrderId(),
            $ord->getOrderEvent(),
            $ord->getDeliveryDate()->format('Y-m-d H:i:s'),
            $ord->getDateModify()->format('Y-m-d H:i:s'),
        );
    }
}