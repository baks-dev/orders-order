<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusDecommission;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusMarketplace;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByEventInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierResult;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileLogisticWarehouse\UserProfileLogisticWarehouseInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Снимает резерв в КАРТОЧКЕ товара при отмене заказа или удалении продукта из заказа
 *  - Canceled «Отменен»
 *  - Decommission «Списание»
 *  - Return «Возврат»
 *
 * @note Работа с резервами в карточке - самый высокий приоритет
 */
#[AsMessageHandler(priority: 999)]
final readonly class ProductsReserveByOrderCancelDispatcher
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private OrderEventInterface $OrderEventRepository,
        private CurrentOrderEventInterface $CurrentOrderEvent,
        private CurrentProductIdentifierByEventInterface $CurrentProductIdentifierRepository,
        private UserProfileLogisticWarehouseInterface $UserProfileLogisticWarehouseRepository,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $messageDispatch,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                (string) $message->getId(),
                self::class,
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** @var  $OrderEvent */
        $OrderEvent = $this->OrderEventRepository
            ->find($message->getEvent());

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->logger->critical(
                'products-sign: Не найдено событие OrderEvent',
                [self::class.':'.__LINE__, var_export($message, true)],
            );

            return;
        }

        /**
         * Если заказ НЕ входит в список статусов, которым доступна отмена резервов - завершаем обработчик:
         * - Canceled «Отменен»
         * - Decommission «Списание»
         */
        if(
            false === $OrderEvent->isStatusEquals(OrderStatusCanceled::class)
            && false === $OrderEvent->isStatusEquals(OrderStatusDecommission::class)
        )
        {
            return;
        }

        /**
         * Если статус текущего - Canceled «Отменен» - получаем предыдущее событие заказа
         */
        if(true === $OrderEvent->isStatusEquals(OrderStatusCanceled::class))
        {
            $LastOrderEvent = $this->OrderEventRepository
                ->find($message->getLast());

            if(false === ($LastOrderEvent instanceof OrderEvent))
            {
                $this->logger->critical(
                    sprintf('orders-order: Не найдено предыдущее событие заказа %s', $OrderEvent->getOrderNumber()),
                    [self::class.':'.__LINE__, var_export($message, true)],
                );

                return;
            }

            /**
             * Не снимаем резерв в карточке, если предыдущее событие заказа Completed «Выполнен»
             * либо Marketplace «Ожидается возврат службой маркетплейса».
             *
             * @note Резерв был списан при завершении
             */
            if(
                true === $LastOrderEvent->isStatusEquals(OrderStatusCompleted::class)
                || true === $LastOrderEvent->isStatusEquals(OrderStatusMarketplace::class)
            )
            {
                $this->logger->critical(
                    sprintf('orders-order: Не снимаем резерв с ранее выполненного заказа %s', $LastOrderEvent->getOrderNumber()),
                    [self::class.':'.__LINE__, var_export($message, true)],
                );

                return;
            }
        }

        /** Получаем активное событие заказа для номера и профиля склада на случай, если статус заказа изменился */
        if(empty($OrderEvent->getOrderNumber()))
        {
            $OrderEvent = $this->CurrentOrderEvent
                ->forOrder($message->getId())
                ->find();

            if(false === ($OrderEvent instanceof OrderEvent))
            {
                $this->logger->critical(
                    'orders-order: Не найдено событие OrderEvent',
                    [self::class.':'.__LINE__, var_export($message, true)],
                );

                return;
            }
        }

        $UserProfileUid = $OrderEvent->getOrderProfile();

        if(false === ($UserProfileUid instanceof UserProfileUid))
        {
            return;
        }

        /**
         * Проверяем, является ли данный профиль логистическим складом
         */

        $isLogisticWarehouse = $this->UserProfileLogisticWarehouseRepository
            ->forProfile($UserProfileUid)
            ->isLogisticWarehouse();

        if(false === $isLogisticWarehouse)
        {
            return;
        }

        /**
         * Запускаем процесс снятия резервов в карточке продукта
         */

        $this->logger->info(
            sprintf('%s: Снимаем общий резерв в карточке товара для заказ со статусом `%s`',
                $OrderEvent->getOrderNumber(),
                $OrderEvent->getStatus()->getOrderStatusValue(),
            ),
            [
                self::class.':'.__LINE__,
                'deduplicator' => $Deduplicator->getKey(),
                var_export($message, true),
            ],
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

            if(false === ($CurrentProductIdentifier instanceof CurrentProductIdentifierResult))
            {
                $this->logger->critical(
                    'orders-order: Продукт не найден',
                    [
                        'product' => (string) $product->getProduct(),
                        'offer' => (string) $product->getOffer(),
                        'variation' => (string) $product->getVariation(),
                        'modification' => (string) $product->getModification(),
                        self::class.':'.__LINE__,
                    ],
                );

                continue;
            }

            $productTotal = $product->getPrice()->getTotal();
            $itemsCount = $product->getItem()->count();

            /** Определяем total по ЕДИНИЦАМ продукции */
            $total = $itemsCount;

            /** Истинное условие */
            $isCorrectItemsCount = $productTotal === $itemsCount;

            if(true === $isCorrectItemsCount)
            {
                $this->logger->info(
                    message: sprintf(
                        '%s: Снимаем резерв в карточке товара по количеству ЕДИНИЦ продукции',
                        $EditOrderDTO->getInvariable()->getNumber(),
                    ),
                    context: [
                        '$productTotal' => $productTotal,
                        '$itemsCount' => $itemsCount,
                        self::class.':'.__LINE__,
                    ],
                );
            }

            if(false === $isCorrectItemsCount)
            {
                $this->logger->warning(
                    message: 'КОЛИЧЕСТВО продукции в заказе не совпадает с ЕДИНИЦАМИ продукта в заказе',
                    context: [
                        '$productTotal' => $productTotal,
                        '$itemsCount' => $itemsCount,
                        self::class.':'.__LINE__],
                );

                /** Переопределяем total из КОЛИЧЕСТВА продукции */
                $total = $productTotal;
            }

            /** Снимаем резерв с карточки отмененного заказа */
            $this->messageDispatch->dispatch(
                message: new ProductsReserveByOrderCancelMessage(
                    $CurrentProductIdentifier->getEvent(),
                    $CurrentProductIdentifier->getOffer(),
                    $CurrentProductIdentifier->getVariation(),
                    $CurrentProductIdentifier->getModification(),
                    $total,
                    $OrderEvent->getOrderNumber(),
                ),
                transport: 'products-product',
            );
        }

        $Deduplicator->save();
    }
}
