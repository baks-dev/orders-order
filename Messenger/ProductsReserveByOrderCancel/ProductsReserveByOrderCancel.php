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

namespace BaksDev\Orders\Order\Messenger\ProductsReserveByOrderCancel;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Работа с резервами в карточке - самый высокий приоритет */
#[AsMessageHandler(priority: 999)]
final class ProductsReserveByOrderCancel
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly DeduplicatorInterface $deduplicator,
        private readonly MessageDispatchInterface $messageDispatch,
        private readonly OrderEventInterface $orderEventRepository,
        LoggerInterface $ordersOrderLogger,
    ) {
        $this->logger = $ordersOrderLogger;
    }

    /** Снимаем резерв с продукции при отмене заказа  */
    public function __invoke(OrderMessage $message): void
    {
        $OrderEvent = $this->orderEventRepository->find($message->getEvent());

        if($OrderEvent === false)
        {
            return;
        }

        /** Если статус заказа не "ОТМЕНА" - завершаем обработчик */
        if(false === $OrderEvent->isStatusEquals(OrderStatusCanceled::class))
        {
            return;
        }

        $this->logger->info(sprintf(
            '%s: Снимаем общий резерв продукции в карточке при отмене заказа:',
            $OrderEvent->getOrderNumber()
        ));

        $EditOrderDTO = new EditOrderDTO();
        $OrderEvent->getDto($EditOrderDTO);

        $Deduplicator = $this->deduplicator
            ->namespace('orders-order');

        /** @var OrderProductDTO $product */
        foreach($EditOrderDTO->getProduct() as $product)
        {
            $Deduplicator->deduplication([
                (string) $message->getId(),
                (string) $product->getProduct(),
                (string) $product->getOffer(),
                (string) $product->getVariation(),
                (string) $product->getModification(),
                OrderStatusCanceled::STATUS,
                md5(self::class)
            ]);

            if($Deduplicator->isExecuted())
            {
                continue;
            }

            /** Снимаем резерв отмененного заказа */
            $this->messageDispatch->dispatch(
                new ProductsReserveByOrderCancelMessage(
                    $product->getProduct(),
                    $product->getOffer(),
                    $product->getVariation(),
                    $product->getModification(),
                    $product->getPrice()->getTotal()
                ),
                transport: 'products-product'
            );

            $Deduplicator->save();
        }
    }
}
