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

namespace BaksDev\Orders\Order\Messenger\ProductReserveByOrderComplete;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Lock\AppLockInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCompleted;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Repository\CurrentQuantity\CurrentQuantityByEventInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Modification\CurrentQuantityByModificationInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Offer\CurrentQuantityByOfferInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Variation\CurrentQuantityByVariationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Работа с резервами в карточке - самый высокий приоритет */
#[AsMessageHandler(priority: 999)]
final class ProductReserveByOrderCompleted
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly OrderEventInterface $orderEventRepository,
        private readonly DeduplicatorInterface $deduplicator,
        private readonly MessageDispatchInterface $messageDispatch,
        LoggerInterface $ordersOrderLogger,
    ) {
        $this->logger = $ordersOrderLogger;
    }


    /**
     * Снимаем резерв и наличие с продукта если заказ выполнен
     */
    public function __invoke(OrderMessage $message): void
    {
        $OrderEvent = $this->orderEventRepository->find($message->getEvent());

        if(!$OrderEvent)
        {
            return;
        }

        /** Если статус не Completed «Выполнен» - завершаем обработчик */
        if(false === $OrderEvent->isStatusEquals(OrderStatusCompleted::class))
        {
            return;
        }

        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                $message,
                OrderStatusCompleted::STATUS,
                self::class
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $this->logger->info(
            '#{number}: Снимаем общий резерв и наличие продукции в карточке при выполненном заказе (см. products-product.log)',
            [
                'number' => $OrderEvent->getOrderNumber(),
                'deduplicator' => $Deduplicator->getKey()
            ]
        );

        $EditOrderDTO = new EditOrderDTO();
        $OrderEvent->getDto($EditOrderDTO);

        /** @var OrderProductDTO $product */
        foreach($EditOrderDTO->getProduct() as $product)
        {
            /** Снимаем резерв и наличие выполненного заказа */
            $this->messageDispatch->dispatch(
                new ProductReserveByOrderCompleteMessage(
                    $product->getProduct(),
                    $product->getOffer(),
                    $product->getVariation(),
                    $product->getModification(),
                    $product->getPrice()->getTotal()
                ),
                transport: 'products-product'
            );
        }

        $Deduplicator->save();
    }
}
