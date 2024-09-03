<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Messenger\ProductReserveByOrderNew;

use BaksDev\Products\Product\Repository\UpdateProductQuantity\AddProductQuantityInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Работа с резервами в карточке - самый высокий приоритет */
#[AsMessageHandler(priority: 999)]
final class ProductReserveByOrderNewHandler
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly AddProductQuantityInterface $addProductQuantity,
        LoggerInterface $productsProductLogger,
    ) {
        $this->logger = $productsProductLogger;
    }

    public function __invoke(ProductReserveByOrderNewMessage $message): void
    {
        /** Log Data */
        $dataLogs['total'] = (string) $message->getTotal();
        $dataLogs['ProductEventUid'] = (string) $message->getEvent();
        $dataLogs['ProductOfferUid'] = (string) $message->getOffer();
        $dataLogs['ProductVariationUid'] = (string) $message->getVariation();
        $dataLogs['ProductModificationUid'] = (string) $message->getModification();

        $result = $this
            ->addProductQuantity
            ->forEvent($message->getEvent())
            ->forOffer($message->getOffer())
            ->forVariation($message->getVariation())
            ->forModification($message->getModification())
            ->addReserve($message->getTotal())
            ->addQuantity(false)
            ->update();


        if($result === false)
        {
            $dataLogs[0] = self::class.':'.__LINE__;
            $this->logger->critical('orders-order: Невозможно добавить резерв на новый заказ: карточка не найдена', $dataLogs);
            return;
        }

        if($result === 0)
        {
            $dataLogs[0] = self::class.':'.__LINE__;
            $this->logger->critical('orders-order: Невозможно добавить резерв на новый заказ: недостаточное количество для резерва', $dataLogs);
            return;
        }

        $dataLogs[0] = self::class.':'.__LINE__;
        $this->logger->info(
            sprintf('orders-order: Добавили %s резерва продукции в карточке', $message->getTotal()),
            $dataLogs
        );
    }
}
