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
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\Items\OrderProductItemDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileLogisticWarehouse\UserProfileLogisticWarehouseInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * НА КАЖДУЮ ЕДИНИЦУ ТОВАРА Обновляем/Снимаем резерв в карточке при изменении количества в заказе
 *
 * @note Работа с резервами в карточке - самый высокий приоритет
 * @note не сработает на новом заказе
 *
 * заменяет работу @see ProductChangeReserveByOrderChangeDispatcher
 */
#[AsMessageHandler(priority: 998)]
final readonly class ProductChangeReserveByOrderProductItemChangeDispatcher
{
    public function __construct(
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private OrderEventInterface $OrderEventRepository,
        private UserProfileLogisticWarehouseInterface $UserProfileLogisticWarehouseRepository,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $messageDispatch,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                $message,
                //                self::class,
                ProductChangeReserveByOrderChangeDispatcher::class, // отработает только если этот обработчик не был вызван первым // @TODO удалить при релизе
            ]);


        if($Deduplicator->isExecuted())
        {
            return;
        }

        $DeduplicatorOrder = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                $message,
                self::class,
            ]);

        if($DeduplicatorOrder->isExecuted())
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

        $OrderEventLast = $this->OrderEventRepository
            ->find($message->getLast());

        if(false === ($OrderEventLast instanceof OrderEvent))
        {
            return;
        }

        $LastOrderDTO = new EditOrderDTO();
        $OrderEventLast->getDto($LastOrderDTO);


        /**
         * Получаем активное событие
         */

        $CurrentOrderEvent = $this->OrderEventRepository
            ->find($message->getEvent());

        if(false === ($CurrentOrderEvent instanceof OrderEvent))
        {
            $this->logger->critical(
                message: 'Не найдено активное событие OrderEvent',
                context: [
                    var_export($message, true),
                    self::class.':'.__LINE__,
                ]
            );

            return;
        }

        $UserProfileUid = $CurrentOrderEvent->getOrderProfile();

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

        $CurrentOrderDTO = new EditOrderDTO();
        $CurrentOrderEvent->getDto($CurrentOrderDTO);

        /**
         * Итерируемся по текущей коллекции продуктов в заказе
         */

        /** @var OrderProductDTO $currentOrderProductDTO */
        foreach($CurrentOrderDTO->getProduct() as $currentOrderProductDTO)
        {
            /** Находим соответствие продукта из актуального и предыдущего состояния ЗАКАЗА */
            $lastOrderProductDTO = $LastOrderDTO->getProduct()->findFirst(function(
                int $k,
                OrderProductDTO $lastProduct
            ) use (
                $currentOrderProductDTO
            ) {
                return $lastProduct->getProduct()->equals($currentOrderProductDTO->getProduct())
                    && ((is_null($lastProduct->getOffer()) === true && is_null($currentOrderProductDTO->getOffer()) === true) || $lastProduct->getOffer()?->equals($currentOrderProductDTO->getOffer()))
                    && ((is_null($lastProduct->getVariation()) === true && is_null($currentOrderProductDTO->getVariation()) === true) || $lastProduct->getVariation()?->equals($currentOrderProductDTO->getVariation()))
                    && ((is_null($lastProduct->getModification()) === true && is_null($currentOrderProductDTO->getModification()) === true) || $lastProduct->getModification()?->equals($currentOrderProductDTO->getModification()));
            });

            /**
             * Если в заказе текущий продукт СОВПАДАЕТ с прошлым продуктом - значит мы ОБНОВИЛИ продукт в заказе:
             * - начинаем сравнивать прошлые и текущие item у продукта
             */
            if(true === $lastOrderProductDTO instanceof OrderProductDTO)
            {
                /** Если коллекция единиц продукта пустая - завершаем весь процесс обработки нового заказа */
                if($currentOrderProductDTO->getItem()->isEmpty() || $lastOrderProductDTO->getItem()->isEmpty())
                {
                    $this->logger->critical(
                        message: 'При сохранении заказа не были добавлены единицы продукта',
                        context: [
                            'current_product' => var_export($currentOrderProductDTO, true),
                            'last_product' => var_export($lastOrderProductDTO, true),
                            var_export($message, true),
                            self::class.':'.__LINE__,
                        ]
                    );

                    return;
                    // throw new \Exception(); // @TODO
                }

                /**
                 * НЕ ИЗМЕНИЛИ количество item -> пропускаем
                 * В текущей и предыдущей коллекции РАВНО
                 */
                if($currentOrderProductDTO->getItem()->count() === $lastOrderProductDTO->getItem()->count())
                {
                    continue;
                }

                $this->logger->info(
                    message: 'Список единиц продукции был изменен. Запущен процесс резервирования и списания',
                    context: [
                        'current_product' => var_export($currentOrderProductDTO, true),
                        'last_product' => var_export($lastOrderProductDTO, true),
                        var_export($message, true),
                        self::class.':'.__LINE__,
                    ]
                );

                // @TODO вернуть при релизе
                /**
                 * Добавляем новый резерв с ВЫСОКИМ приоритетом
                 */
                //                $this->messageDispatch->dispatch(
                //                    new ProductReserveByOrderNewMessage(
                //                        $currentOrderProductDTO->getProduct(),
                //                        $currentOrderProductDTO->getOffer(),
                //                        $currentOrderProductDTO->getVariation(),
                //                        $currentOrderProductDTO->getModification(),
                //                        $currentOrderProductDTO->getItem()->count(),
                //                    ),
                //                    transport: 'products-product',
                //                );

                // @TODO вернуть при релизе
                /**
                 * Снимаем предыдущий резерв как отложенное сообщение с НИЗКИМ приоритетом
                 */
                //                $this->messageDispatch->dispatch(
                //                    new ProductsReserveByOrderCancelMessage(
                //                        $lastOrderProductDTO->getProduct(),
                //                        $lastOrderProductDTO->getOffer(),
                //                        $lastOrderProductDTO->getVariation(),
                //                        $lastOrderProductDTO->getModification(),
                //                        $lastOrderProductDTO->getItem()->count(),
                //                    ),
                //                    stamps: [new MessageDelay('5 seconds')],
                //                    transport: 'products-product-low',
                //                );

            }

            /**
             * Если в заказе текущий продукт НЕ СОВПАДАЕТ с прошлым продуктом - значит мы ДОБАВИЛИ продукт в заказ:
             * - начинаем сравнивать прошлые и текущие item у продукта
             */
            if(false === $lastOrderProductDTO instanceof OrderProductDTO)
            {
                /** Если коллекция единиц продукта пустая - завершаем весь процесс обработки нового заказа */
                if($currentOrderProductDTO->getItem()->isEmpty())
                {
                    $this->logger->critical(
                        message: 'При сохранении заказа не были добавлены единицы продукта',
                        context: [
                            'current_product' => var_export($currentOrderProductDTO, true),
                            'last_product' => var_export($lastOrderProductDTO, true),
                            var_export($message, true),
                            self::class.':'.__LINE__,
                        ]
                    );

                    return;
                    // throw new \Exception(); // @TODO
                }

                /** @var OrderProductItemDTO $currentOrderProductItemDTO */
                foreach($currentOrderProductDTO->getItem() as $currentOrderProductItemDTO)

                    $this->logger->info(
                        message: 'Добавляем резерв при добавлении нового ПРОДУКТА в заказ',
                        context: [
                            'new_product_item' => var_export($currentOrderProductItemDTO, true),
                            'current_product' => var_export($currentOrderProductDTO, true),
                            'last_product' => var_export($lastOrderProductDTO, true),
                            var_export($message, true),
                            self::class.':'.__LINE__,
                        ]
                    );

                // @TODO вернуть при релизе
                //
                //                $this->messageDispatch->dispatch(
                //                    new ProductReserveByOrderNewMessage(
                //                        $currentOrderProductDTO->getProduct(),
                //                        $currentOrderProductDTO->getOffer(),
                //                        $currentOrderProductDTO->getVariation(),
                //                        $currentOrderProductDTO->getModification(),
                //                        $currentOrderProductDTO->getItem()->count(),
                //                    ),
                //                    transport: 'products-product',
                //                );
            }
        }

        $DeduplicatorOrder->save();
    }
}
