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

namespace BaksDev\Orders\Order\Repository\OrderDelivery;

use BaksDev\Contacts\Region\Entity\Call\ContactsRegionCall;
use BaksDev\Contacts\Region\Entity\Call\Info\ContactsRegionCallInfo;
use BaksDev\Contacts\Region\Entity\ContactsRegion;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Products\Stocks\Entity\Move\ProductStockMove;
use BaksDev\Products\Stocks\Entity\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Type\Event\ProductStockEventUid;

final class OrderDeliveryRepository implements OrderDeliveryInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает геоданные складской заявки.
     */
    public function fetchProductStocksGps(ProductStockEventUid $event): array|bool
    {

        $qbOrder = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qbOrder->from(ProductStockOrder::class, 'stock_order');

        $qbOrder->join(
            'stock_order',
            Order::class,
            'ord',
            'ord.id = stock_order.ord'
        );

        $qbOrder->join(
            'ord',
            OrderEvent::class,
            'event',
            'event.id = ord.event'
        );

        $qbOrder->join(
            'event',
            OrderUser::class,
            'users',
            'users.event = event.id'
        );

        /* Геоданные доставки заказа */
        $qbOrder->select('delivery.latitude');
        $qbOrder->addSelect('delivery.longitude');

        $qbOrder->join(
            'users',
            OrderDelivery::class,
            'delivery',
            'delivery.usr = users.id'
        );

        $qbOrder->where('stock_order.event = :event');


        /**
         * ПЕРЕМЕЩЕНИЕ
         */

        $qbMove = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qbMove->from(ProductStockMove::class, 'stock_move');

        $qbMove->join(
            'stock_move',
            ContactsRegionCall::class,
            'call',
            'call.const = stock_move.destination '
        );

        /* Геоданные склада перемещения */
        $qbMove->addSelect('call_info.latitude');
        $qbMove->addSelect('call_info.longitude');

        $qbMove->join(
            'call',
            ContactsRegion::class,
            'region',
            'region.event = call.event'
        );

        $qbMove->join(
            'call',
            ContactsRegionCallInfo::class,
            'call_info',
            'call_info.call = call.id'
        );

        $qbMove->where('stock_move.event = :event');

        /** Выполняем результат запроса UNION */
        $qb = $this->DBALQueryBuilder->prepare($qbOrder->getSQL().' UNION '.$qbMove->getSQL());
        $qb->bindValue('event', $event, ProductStockEventUid::TYPE);

        return $qb->executeQuery()->fetchAssociative();

    }

}
