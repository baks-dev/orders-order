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

namespace BaksDev\Orders\Order\Messenger\ProductsReserveByOrderCancel;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Работа с резервами в карточке - самый высокий приоритет */
#[AsMessageHandler(priority: 999)]
final readonly class ProductsReserveByOrderCancel
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private OrderEventInterface $orderEventRepository,
        private CurrentProductIdentifierInterface $CurrentProductIdentifierRepository,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $messageDispatch,
    ) {}

    /**
     * Снимаем резерв с продукции при отмене заказа
     */
    public function __invoke(OrderMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                (string) $message->getId(),
                OrderStatusCanceled::STATUS,
                self::class
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $OrderEvent = $this->orderEventRepository->find($message->getEvent());

        if($OrderEvent === false)
        {
            return;
        }

        /** Если статус не "ОТМЕНА" - завершаем обработчик */
        if(false === $OrderEvent->isStatusEquals(OrderStatusCanceled::class))
        {
            return;
        }

        $this->logger->info(
            '#{number}: Снимаем общий резерв продукции в карточке при отмене заказа (см. products-product.log)',
            [
                'number' => $OrderEvent->getOrderNumber(),
                'status' => OrderStatusCanceled::STATUS,
                'deduplicator' => $Deduplicator->getKey()
            ]
        );

        $EditOrderDTO = new EditOrderDTO();
        $OrderEvent->getDto($EditOrderDTO);

        /** @var OrderProductDTO $product */
        foreach($EditOrderDTO->getProduct() as $product)
        {
            /** Получаем активные идентификаторы карточки на случай, если товар обновлялся */

            $CurrentProductIdentifier = $this->CurrentProductIdentifierRepository
                ->forEvent($product->getProduct())
                ->forOffer($product->getOffer())
                ->forVariation($product->getVariation())
                ->forModification($product->getModification())
                ->find();

            /** Снимаем резерв отмененного заказа */

            $this->messageDispatch->dispatch(
                new ProductsReserveByOrderCancelMessage(
                    $CurrentProductIdentifier->getEvent(),
                    $CurrentProductIdentifier->getOffer(),
                    $CurrentProductIdentifier->getVariation(),
                    $CurrentProductIdentifier->getModification(),
                    $product->getPrice()->getTotal()
                ),
                transport: 'products-product'
            );
        }

        $Deduplicator->save();
    }
}
