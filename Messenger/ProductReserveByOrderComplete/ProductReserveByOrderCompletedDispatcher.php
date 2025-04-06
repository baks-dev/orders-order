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

namespace BaksDev\Orders\Order\Messenger\ProductReserveByOrderComplete;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * При изменении статуса заказа - снимаем резерв и наличие с продукта если заказ выполнен
 * @note Работа с резервами в карточке - самый высокий приоритет
 */
#[AsMessageHandler(priority: 900)]
final readonly class ProductReserveByOrderCompletedDispatcher
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
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
                (string) $message->getId(),
                self::class
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $OrderEvent = $this->OrderEventRepository
            ->find($message->getEvent());

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->logger->critical(
                'products-sign: Не найдено событие OrderEvent',
                [self::class.':'.__LINE__, var_export($message, true)]
            );

            return;
        }

        /** Если статус не Completed «Выполнен» - завершаем обработчик */
        if(false === $OrderEvent->isStatusEquals(OrderStatusCompleted::class))
        {
            return;
        }

        /** Получаем активное событие заказа в случае если статус заказа изменился */
        if(empty($OrderEvent->getOrderNumber()))
        {
            $OrderEvent = $this->CurrentOrderEvent
                ->forOrder($message->getId())
                ->find();

            if(false === ($OrderEvent instanceof OrderEvent))
            {
                $this->logger->critical(
                    'orders-order: Не найдено событие OrderEvent',
                    [self::class.':'.__LINE__, var_export($message, true)]
                );

                return;
            }
        }

        $this->logger->info(
            sprintf(
                '#%s: Снимаем общий резерв и наличие продукции в карточке при выполненном заказе (см. products-product.log)',
                $OrderEvent->getOrderNumber()
            ),
            [
                self::class.':'.__LINE__,
                'deduplicator' => $Deduplicator->getKey(),
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
