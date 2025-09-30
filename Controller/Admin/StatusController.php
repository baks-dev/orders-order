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

namespace BaksDev\Orders\Order\Controller\Admin;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Delivery\BaksDevDeliveryBundle;
use BaksDev\DeliveryTransport\Type\OrderStatus\OrderStatusDelivery;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Forms\Status\StatusDTO;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusExtradition;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCollection;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use BaksDev\Orders\Order\Forms\Status\StatusForm;
use Symfony\Component\HttpFoundation\Request;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_STATUS')]
final class StatusController extends AbstractController
{
    #[Route(
        '/admin/order/status/{status}',
        name: 'admin.status',
        methods: ['POST'],
        condition: "request.headers.get('X-Requested-With') === 'XMLHttpRequest'",
    )]
    public function status(
        Request $request,
        CurrentOrderEventInterface $currentOrderEvent,
        OrderStatusCollection $orderStatusCollection,
        OrderStatusHandler $handler,
        CentrifugoPublishInterface $publish,
        ExistOrderEventByStatusInterface $existOrderEventByStatus,
        string $status,
    ): Response
    {
        $statusDTO = new StatusDTO();
        $statusForm = $this->createForm(StatusForm::class, $statusDTO, [
            'action' => $this->generateUrl('orders-order:admin.status', ['status' => $status]),
        ]);

        $statusForm->handleRequest($request);

        $unsuccessful = [];
        $orders = [];

        if($statusForm->isSubmitted())
        {
            foreach($statusDTO->getOrders() as $order)
            {
                $orderEvent = $currentOrderEvent
                    ->forOrder($order->getId())
                    ->find();

                if(false === ($orderEvent instanceof OrderEvent))
                {
                    throw new InvalidArgumentException('Invalid Order Event');
                }

                /** @var EditOrderDTO $editOrderDTO */
                $editOrderDTO = $orderEvent->getDto(new EditOrderDTO());
                $currentPriority = $editOrderDTO->getStatus()->getOrderStatus()::priority();

                $orderStatus = $orderStatusCollection->from($status);

                $orderStatusDTO = new OrderStatusDTO(
                    $orderStatus,
                    $orderEvent->getId()
                )
                ->setProfile($this->getProfileUid());


                /**
                 * Статус заказа можно двигать только вперед
                 */
                if($currentPriority >= $orderStatus->getOrderStatus()::priority())
                {
                    $unsuccessful[] = $order->getNumber();
                    continue;
                }

                /**
                 * Обновляем статус заказа
                 */

                /** Невозможно применить повторно статус */
                $isExistsStatus = $existOrderEventByStatus
                    ->forOrder($order->getId())
                    ->forStatus($orderStatusDTO->getStatus())
                    ->isExists();

                if($isExistsStatus)
                {
                    $unsuccessful[] = $order->getNumber();
                    continue;
                }

                /**
                 * Изменить статус выполненного заказа невозможно
                 */
                $isExistsCompleted = $existOrderEventByStatus
                    ->forOrder($order->getId())
                    ->forStatus(OrderStatusCompleted::class)
                    ->isExists();

                if($isExistsCompleted)
                {
                    $unsuccessful[] = $order->getNumber();
                    continue;
                }

                if(class_exists(BaksDevProductsStocksBundle::class))
                {
                    /**
                     * Если имеется модуль склада - в статус Completed «Выполнен» можно только после сборки
                     * Extradition «Готов к выдаче»
                     */
                    if(true === $orderStatusDTO->getStatus()->equals(OrderStatusCompleted::class))
                    {
                        $isExists = $existOrderEventByStatus
                            ->forOrder($order->getId())
                            ->forStatus(OrderStatusExtradition::class)
                            ->isExists();

                        if($isExists === false)
                        {
                            $unsuccessful[] = $order->getNumber();
                            continue;
                        }
                    }

                    /** Изменить статус на Статус Extradition «Готов к выдаче» можно только через склад */
                    if(true === $orderStatusDTO->getStatus()->equals(OrderStatusExtradition::class))
                    {
                        $unsuccessful[] = $order->getNumber();
                        continue;
                    }
                }

                if(class_exists(BaksDevDeliveryBundle::class))
                {
                    /** Изменить статус на Статус Delivery «Доставка (погружен в транспорт)» можно только через Доставку */
                    if(true === $orderStatusDTO->getStatus()->equals(OrderStatusDelivery::class))
                    {
                        $unsuccessful[] = $order->getNumber();
                        continue;
                    }
                }

                $OrderStatusHandler = $handler->handle($orderStatusDTO);

                if(false === $OrderStatusHandler instanceof Order)
                {
                    $unsuccessful[] = $order->getNumber();
                    continue;
                }

                // Отправляем сокет для скрытия заказа у других менеджеров
                $socket = $publish
                    ->addData(['order' => (string) $OrderStatusHandler->getId()])
                    ->addData(['profile' => (string) $this->getCurrentProfileUid()])
                    ->send('orders');

                if($socket && $socket->isError())
                {
                    return new JsonResponse($socket->getMessage());
                }

                $orders[] = $order->getId();
            }

            if(true === empty($unsuccessful))
            {
                return new JsonResponse(
                    [
                        'type' => 'success',
                        'header' => 'Заказы #'.implode(',', $orders),
                        'message' => 'Статусы успешно обновлены',
                        'status' => 200,
                    ],
                    200
                );
            }
        }

        if(false === empty($unsuccessful))
        {
            return new JsonResponse(
                [
                    'type' => 'danger',
                    'header' => 'Заказы #'.implode(',', $unsuccessful),
                    'message' => 'Ошибка обновления заказов',
                    'status' => 400,
                ],
                400,
            );
        }

        return new JsonResponse(
            [
                'type' => 'danger',
                'header' => 'Заказы',
                'message' => 'Ошибка обновления заказов',
                'status' => 400,
            ],
        );
    }
}
