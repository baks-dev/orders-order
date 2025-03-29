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

namespace BaksDev\Orders\Order\Messenger\ProductChangeReserveByOrder;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Messenger\ProductReserveByOrderNew\ProductReserveByOrderNewMessage;
use BaksDev\Orders\Order\Messenger\ProductsReserveByOrderCancel\ProductsReserveByOrderCancelMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Обновляем резерв в карточке при изменении количества в заказе
 * @note Работа с резервами в карточке - самый высокий приоритет
 */
#[AsMessageHandler(priority: 999)]
final readonly class ProductChangeReserveByOrderChangeDispatcher
{
    public function __construct(
        private OrderEventInterface $OrderEventRepository,
        private CurrentOrderEventInterface $CurrentOrderEvent,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $messageDispatch,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                $message,
                self::class
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** Для пересчета резервов требуется предыдущее событие */
        if(false === ($message->getLast() instanceof OrderEventUid))
        {
            return;
        }

        /**
         * Получаем предыдущее событие
         */

        $OrderEvent = $this->OrderEventRepository
            ->find($message->getLast());

        if($OrderEvent === false)
        {
            return;
        }

        $LastOrderDTO = new EditOrderDTO();
        $OrderEvent->getDto($LastOrderDTO);


        /**
         * Получаем активное событие
         */

        $CurrentOrderEvent = $this->CurrentOrderEvent
            ->forOrder($message->getId())
            ->find();

        if($CurrentOrderEvent === false)
        {
            return;
        }


        $CurrentOrderDTO = new EditOrderDTO();
        $CurrentOrderEvent->getDto($CurrentOrderDTO);

        // Пройдемся по первой коллекции

        /** @var OrderProductDTO $lastProduct */
        foreach($LastOrderDTO->getProduct() as $lastProduct)
        {
            // Найдем в второй коллекции DTO с совпадающими event, offer, variation, modification
            $matching = $CurrentOrderDTO->getProduct()->filter
            (
                function(OrderProductDTO $currentProduct) use ($lastProduct) {
                    return
                        $currentProduct->getProduct()->equals($lastProduct->getProduct())
                        && ((is_null($currentProduct->getOffer()) === true && is_null($lastProduct->getOffer()) === true) || $currentProduct->getOffer()->equals($lastProduct->getOffer()))
                        && ((is_null($currentProduct->getVariation()) === true && is_null($lastProduct->getVariation()) === true) || $currentProduct->getVariation()->equals($lastProduct->getVariation()))
                        && ((is_null($currentProduct->getModification()) === true && is_null($lastProduct->getModification()) === true) || $currentProduct->getModification()->equals($lastProduct->getModification()))
                        && $currentProduct->getPrice()->getTotal() !== $lastProduct->getPrice()->getTotal();
                }
            );

            $currentProduct = $matching->current();

            // Если нашли совпадения с разными total, добавляем в очередь на изменение остатков

            if($currentProduct instanceof OrderProductDTO)
            {
                $this->messageDispatch->dispatch(
                    new ProductsReserveByOrderCancelMessage(
                        $lastProduct->getProduct(),
                        $lastProduct->getOffer(),
                        $lastProduct->getVariation(),
                        $lastProduct->getModification(),
                        $lastProduct->getPrice()->getTotal()
                    )
                );

                $this->messageDispatch->dispatch(
                    new ProductReserveByOrderNewMessage(
                        $currentProduct->getProduct(),
                        $currentProduct->getOffer(),
                        $currentProduct->getVariation(),
                        $currentProduct->getModification(),
                        $currentProduct->getPrice()->getTotal()
                    ),
                    transport: 'products-product'
                );
            }
        }
    }

}
