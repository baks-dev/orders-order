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

namespace BaksDev\Orders\Order\Messenger\ProductsReserveByOrderCancel;

use BaksDev\Products\Product\Repository\UpdateProductQuantity\SubProductQuantityInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Работа с резервами в карточке - самый высокий приоритет */
#[AsMessageHandler(priority: 999)]
final class ProductsReserveByOrderCancelHandler
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly SubProductQuantityInterface $subProductQuantity,
        LoggerInterface $productsProductLogger,
    )
    {
        $this->logger = $productsProductLogger;
    }

    public function __invoke(ProductsReserveByOrderCancelMessage $message): void
    {
        $result = $this
            ->subProductQuantity
            ->forEvent($message->getEvent())
            ->forOffer($message->getOffer())
            ->forVariation($message->getVariation())
            ->forModification($message->getModification())
            ->subReserve($message->getTotal())
            ->subQuantity(false)
            ->update();

        if($result === 0)
        {
            $this->logger->critical(
                'Невозможно снять резерв с карточки товара при отмене заказа: карточка не найдена либо недостаточное количество в резерве)',
                [
                    self::class.':'.__LINE__,
                    'total' => (string) $message->getTotal(),
                    'ProductEventUid' => (string) $message->getEvent(),
                    'ProductOfferUid' => (string) $message->getOffer(),
                    'ProductVariationUid' => (string) $message->getVariation(),
                    'ProductModificationUid' => (string) $message->getModification(),
                ]
            );

            return;
        }

        $this->logger->info(
            'Сняли общий резерв продукции в карточке при отмене заказа',
            [
                self::class.':'.__LINE__,
                'total' => (string) $message->getTotal(),
                'ProductEventUid' => (string) $message->getEvent(),
                'ProductOfferUid' => (string) $message->getOffer(),
                'ProductVariationUid' => (string) $message->getVariation(),
                'ProductModificationUid' => (string) $message->getModification(),
            ]
        );
    }
}
