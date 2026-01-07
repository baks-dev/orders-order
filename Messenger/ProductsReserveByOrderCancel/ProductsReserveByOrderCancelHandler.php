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

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByEventInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierResult;
use BaksDev\Products\Product\Repository\UpdateProductQuantity\SubProductQuantityInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Снимает резерв в КАРТОЧКЕ товара
 *
 * @note Работа с резервами в карточке - самый высокий приоритет
 */
#[AsMessageHandler(priority: 999)]
final readonly class ProductsReserveByOrderCancelHandler
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private MessageDispatchInterface $messageDispatch,
        private SubProductQuantityInterface $subProductQuantityRepository,
        private CurrentProductIdentifierByEventInterface $CurrentProductIdentifierRepository,
    ) {}

    public function __invoke(ProductsReserveByOrderCancelMessage $message): void
    {
        /**
         * Всегда пробуем определить активное состояние карточки на случай обновления
         */

        $CurrentProductIdentifierResult = $this->CurrentProductIdentifierRepository
            ->forEvent($message->getEvent())
            ->forOffer($message->getOffer())
            ->forVariation($message->getVariation())
            ->forModification($message->getModification())
            ->find();

        if(false === ($CurrentProductIdentifierResult instanceof CurrentProductIdentifierResult))
        {
            $this->logger->critical(
                message: sprintf(
                    '%s Невозможно снять резерв с карточки товара: не найдены активные идентификаторы продукта',
                    $message->getNumber() ?? 'orders-order',
                ),
                context: [self::class.':'.__LINE__, var_export($message, true),],
            );

            return;
        }

        $result = $this
            ->subProductQuantityRepository
            ->forEvent($CurrentProductIdentifierResult->getEvent())
            ->forOffer($CurrentProductIdentifierResult->getOffer())
            ->forVariation($CurrentProductIdentifierResult->getVariation())
            ->forModification($CurrentProductIdentifierResult->getModification())
            ->subReserve($message->getTotal())
            ->subQuantity(false)
            ->update();

        if($result === 0)
        {
            $this->logger->critical(
                message: sprintf(
                    '%s Невозможно снять резерв с карточки товара: карточка не найдена либо недостаточное количество в резерве',
                    $message->getNumber() ?? 'orders-order',
                ),
                context: [self::class.':'.__LINE__, var_export($message, true),],
            );

            return;
        }

        $this->messageDispatch->addClearCacheOther('products-product');

        $this->logger->info(
            message: sprintf(
                '%s Сняли общий резерв продукции в карточке. Сняли с резерва %s',
                $message->getNumber() ?? 'orders-order',
                $message->getTotal(),
            ),
            context: [self::class.':'.__LINE__, var_export($message, true),],
        );
    }
}
