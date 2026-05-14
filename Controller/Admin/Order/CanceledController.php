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
 *
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Controller\Admin\Order;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Forms\Canceled\CanceledOrdersDTO;
use BaksDev\Orders\Order\Forms\Canceled\CanceledOrdersForm;
use BaksDev\Orders\Order\Forms\Canceled\Orders\CanceledOrdersOrderDTO;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusMarketplace;
use BaksDev\Orders\Order\UseCase\Admin\Canceled\CanceledOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Canceled\CanceledOrderStatusHandler;
use BaksDev\Orders\Order\UseCase\Admin\Canceled\ReturnOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use BaksDev\Products\Stocks\Entity\Stock\Lock\ProductStockLock;
use BaksDev\Products\Stocks\Messenger\Lock\ProductStockLockMessage;
use BaksDev\Products\Stocks\Repository\ProductStocksByOrder\ProductStocksByOrderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @note Блокирует складскую заявку
 */
#[AsController]
#[RoleSecurity('ROLE_ORDERS_STATUS')]
final class CanceledController extends AbstractController
{
    /** Отмена заказа с указанием причины */
    #[Route('/admin/order/canceled', name: 'admin.order.canceled', methods: ['GET', 'POST'])]
    public function canceled(
        #[Target('ordersOrderLogger')] LoggerInterface $logger,
        Request $request,
        DeduplicatorInterface $deduplicator,
        EntityManagerInterface $EntityManager,
        MessageDispatchInterface $messageDispatch,
        CentrifugoPublishInterface $publish,
        OrderStatusHandler $orderStatusHandler,
        ExistOrderEventByStatusInterface $ExistOrderEventByStatusRepository,
        ?ProductStocksByOrderInterface $productStocksByOrderRepository = null,
    ): Response
    {
        $canceledOrdersDTO = new CanceledOrdersDTO();
        $canceledOrdersForm = $this->createForm(
            CanceledOrdersForm::class,
            $canceledOrdersDTO,
            ['action' => $this->generateUrl('orders-order:admin.order.canceled')],
        )
            ->handleRequest($request);

        if(
            $canceledOrdersForm->isSubmitted()
            && $canceledOrdersForm->isValid()
            && $canceledOrdersForm->has('order_cancel')
        )
        {

            $this->refreshTokenForm($canceledOrdersForm);

            $unsuccessful = [];

            foreach($canceledOrdersDTO->getOrders() as $order)
            {
                $deduplicatorExec = $deduplicator
                    ->namespace('orders-order')
                    ->deduplication([
                        (string) $order->getId(),
                        self::class,
                    ]);

                if($deduplicatorExec->isExecuted())
                {
                    continue;
                }

                /** Пробуем найти по идентификатору заказа */
                $orderMain = $EntityManager->getRepository(Order::class)->find($order->getId());

                if(false === ($orderMain instanceof Order))
                {
                    continue;
                }

                $orderEvent = $EntityManager->getRepository(OrderEvent::class)->find($orderMain->getEvent());

                if(false === ($orderEvent instanceof OrderEvent))
                {
                    continue;
                }

                /** Проверяем, что заказ не был отменен */
                $isExists = $ExistOrderEventByStatusRepository
                    ->forOrder($order->getId())
                    ->forStatus(OrderStatusCanceled::class)
                    ->isExists();

                if(true === $isExists)
                {
                    return new JsonResponse(
                        [
                            'type' => 'success',
                            'header' => sprintf('%s: Отмена заказа', $orderEvent->getOrderNumber()),
                            'message' => 'Не возможно повторно отменить заказ',
                            'status' => 400,
                        ],
                        400,
                    );
                }

                /** По умолчанию заказ отменяется со статусом Canceled «Отменен» */
                $orderCanceledDTO = new CanceledOrderDTO();

                /**
                 * Если текущий статус заказа
                 *
                 * - Completed «Выполнен»
                 * - Marketplace «Ожидается возврат службой маркетплейса»
                 *
                 * - применяем заказу статус Return «Возврат»
                 */
                if(
                    $orderEvent->isStatusEquals(OrderStatusCompleted::class)
                    || $orderEvent->isStatusEquals(OrderStatusMarketplace::class)
                )
                {
                    $orderCanceledDTO = new ReturnOrderDTO();
                }

                $orderEvent->getDto($orderCanceledDTO);

                /** Присваиваем комментарий из формы только в случае, если не было комментария у заказа */
                if(empty($orderCanceledDTO->getComment()))
                {
                    $orderCanceledDTO->setComment($canceledOrdersDTO->getComment());
                }

                $Order = $orderStatusHandler->handle($orderCanceledDTO);

                if(false === ($Order instanceof Order))
                {
                    $unsuccessful[] = $orderEvent->getOrderNumber();
                    continue;
                }

                /** Синхронно блокируем складскую заявку */
                if(true === class_exists(BaksDevProductsStocksBundle::class))
                {
                    /**
                     * Находим событие складской заявки связанной с заказом
                     *
                     * @note при установленном модуле products-stocks у отмененного заказа должна быть созданная складская заявка
                     */
                    $ProductStockEventArray = $productStocksByOrderRepository
                        ->onOrder($Order->getId())
                        ->findAll();

                    if(true === empty($ProductStockEventArray))
                    {
                        $logger->warning(
                            message: 'Не найдено складской заявки, связанной с заказом',
                            context: [self::class.':'.__LINE__],
                        );
                    }

                    if(false === empty($ProductStockEventArray))
                    {
                        /** @note в массиве всегда одна складская заявка */
                        foreach($ProductStockEventArray as $ProductStockEvent)
                        {
                            /** Если нет связи с блокировкой - пропускаем */
                            if(false === ($ProductStockEvent->getLock() instanceof ProductStockLock))
                            {
                                continue;
                            }

                            /** Синхронно ставим блокировку у СЗ */

                            $ProductStockLockMessage = new ProductStockLockMessage(
                                id: $ProductStockEvent->getMain(),
                                context: self::class.':'.__LINE__,
                            );

                            $messageDispatch->dispatch(message: $ProductStockLockMessage);
                        }
                    }
                }

                $deduplicatorExec->save();
            }

            if(true === empty($unsuccessful))
            {
                return new JsonResponse(
                    [
                        'type' => 'success',
                        'header' => 'Отмена заказов',
                        'message' => 'Статусы заказов '.implode(',', $unsuccessful).' успешно обновлены',
                        'status' => 200,
                    ],
                    200,
                );
            }

            $this->addFlash(
                'page.cancel',
                'danger.cancel',
                'orders-order.admin',
                $unsuccessful,
            );

            return $this->redirectToReferer();  // отмена заказа может происходить в других разделах
        }

        $numbers = [];
        /**
         * Если не было сабмита формы - рендерим её
         *
         * @var CanceledOrdersOrderDTO $order
         */
        foreach($canceledOrdersDTO->getOrders() as $order)
        {
            /** Пробуем найти по идентификатору заказа */
            $orderMain = $EntityManager->getRepository(Order::class)->find($order->getId());

            if(false === ($orderMain instanceof Order))
            {
                continue;
            }

            $orderEvent = $EntityManager->getRepository(OrderEvent::class)->find($orderMain->getEvent());

            if(false === ($orderEvent instanceof OrderEvent))
            {
                continue;
            }

            /**
             * Отправляем сокет для скрытия заказа
             */
            $publish
                ->addData([
                    'order' => (string) $orderEvent->getMain(),
                    'profile' => false, // у всех
                    'context' => self::class.':'.__LINE__,
                ])
                ->send('orders');

            $numbers[] = $orderEvent->getOrderNumber();

        }

        /** Если заказ один и имеется комментарий к заказу - присваиваем комментарий из заказа */
        if(
            isset($orderEvent)
            && false === empty($orderEvent->getComment())
            && $canceledOrdersDTO->getOrders()->count() === 1
        )
        {
            $canceledOrdersDTO->setComment($orderEvent->getComment());
        }

        return $this->render([
            'form' => $canceledOrdersForm->createView(),
            'numbers' => $numbers,
        ]);
    }
}