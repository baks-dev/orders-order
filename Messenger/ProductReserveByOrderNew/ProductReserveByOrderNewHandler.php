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

namespace BaksDev\Orders\Order\Messenger\ProductReserveByOrderNew;

use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByEventInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierResult;
use BaksDev\Products\Product\Repository\UpdateProductQuantity\AddProductQuantityInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Добавить резерв на новый заказ
 * @note Работа с резервами в карточке - самый высокий приоритет
 */
#[AsMessageHandler(priority: 999)]
final readonly class ProductReserveByOrderNewHandler
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private AddProductQuantityInterface $addProductQuantity,
        private CurrentProductIdentifierByEventInterface $CurrentProductIdentifier,
    ) {}

    public function __invoke(ProductReserveByOrderNewMessage $message): void
    {
        /**
         * Всегда пробуем определить активное состояние карточки на случай обновления
         */

        $CurrentProductIdentifierResult = $this->CurrentProductIdentifier
            ->forEvent($message->getEvent())
            ->forOffer($message->getOffer())
            ->forVariation($message->getVariation())
            ->forModification($message->getModification())
            ->find();

        if(false === ($CurrentProductIdentifierResult instanceof CurrentProductIdentifierResult))
        {
            $this->logger->critical(
                'orders-order: Невозможно добавить резерв на новый заказ: карточка не найдена',
                [var_export($message, true), self::class.':'.__LINE__],
            );

            return;
        }

        $result = $this
            ->addProductQuantity
            ->forEvent($CurrentProductIdentifierResult->getEvent())
            ->forOffer($CurrentProductIdentifierResult->getOffer())
            ->forVariation($CurrentProductIdentifierResult->getVariation())
            ->forModification($CurrentProductIdentifierResult->getModification())
            ->addReserve($message->getTotal())
            ->addQuantity(false)
            ->update();

        if($result === false)
        {
            $this->logger->critical(
                'orders-order: Невозможно добавить резерв на новый заказ: карточка не найдена',
                [var_export($message, true), self::class.':'.__LINE__]
            );

            return;
        }

        if($result === 0)
        {
            $this->logger->critical(
                'orders-order: Невозможно добавить резерв на новый заказ: недостаточное количество для резерва',
                [var_export($message, true), self::class.':'.__LINE__]
            );

            return;
        }

        $this->logger->info(
            sprintf('orders-order: Добавили резерв продукции в карточке. Резерв равен %s', $message->getTotal()),
            [var_export($message, true), self::class.':'.__LINE__]
        );
    }
}
