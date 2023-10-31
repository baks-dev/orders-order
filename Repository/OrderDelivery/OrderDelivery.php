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
use BaksDev\Orders\Order\Entity as EntityOrder;
use BaksDev\Products\Stocks\Entity\Move\ProductStockMove;
use BaksDev\Products\Stocks\Entity\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Type\Event\ProductStockEventUid;
use Doctrine\DBAL\Connection;

final class OrderDelivery implements OrderDeliveryInterface
{
    private Connection $connection;

    public function __construct(
        Connection $connection,
    ) {
        $this->connection = $connection;
    }

    /**
     * Метод возвращает геоданные складской заявки.
     */
    public function fetchProductStocksGps(ProductStockEventUid $event): array|bool
    {
        /**
         * ДОСТАВКА
         */

        $qbOrder = $this->connection->createQueryBuilder();

        $qbOrder->from(ProductStockOrder::TABLE, 'stock_order');

        $qbOrder->join('stock_order', EntityOrder\Order::TABLE, 'ord', 'ord.id = stock_order.ord');

        $qbOrder->join(
            'ord',
            EntityOrder\Event\OrderEvent::TABLE,
            'event',
            'event.id = ord.event'
        );

        $qbOrder->join(
            'event',
            EntityOrder\User\OrderUser::TABLE,
            'users',
            'users.event = event.id'
        );

        /* Геоданные доставки заказа */
        $qbOrder->select('delivery.latitude');
        $qbOrder->addSelect('delivery.longitude');

        $qbOrder->join(
            'users',
            EntityOrder\User\Delivery\OrderDelivery::TABLE,
            'delivery',
            'delivery.usr = users.id'
        );

        $qbOrder->where('stock_order.event = :event');


        /**
         * ПЕРЕМЕЩЕНИЕ
         */

        $qbMove = $this->connection->createQueryBuilder();
  
        $qbMove->from(ProductStockMove::TABLE, 'stock_move');

        $qbMove->join(
            'stock_move',
            ContactsRegionCall::TABLE,
            'call',
            'call.const = stock_move.destination '
        );

        /* Геоданные склада перемещения */
        $qbMove->addSelect('call_info.latitude');
        $qbMove->addSelect('call_info.longitude');

        $qbMove->join(
            'call',
            ContactsRegion::TABLE,
            'region',
            'region.event = call.event'
        );

        $qbMove->join(
            'call',
            ContactsRegionCallInfo::TABLE,
            'call_info',
            'call_info.call = call.id'
        );

        $qbMove->where('stock_move.event = :event');

        /** Выполняем результат запроса UNION */
        $qb = $this->connection->prepare($qbOrder->getSQL().' UNION '.$qbMove->getSQL().' ');
        $qb->bindValue('event', $event, ProductStockEventUid::TYPE);

        return $qb->executeQuery()->fetchAssociative();

    }

}
