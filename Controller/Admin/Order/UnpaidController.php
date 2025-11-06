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

namespace BaksDev\Orders\Order\Controller\Admin\Order;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Forms\Unpaid\UnpaidOrdersDTO;
use BaksDev\Orders\Order\Forms\Unpaid\UnpaidOrdersForm;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPackage;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCollection;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_STATUS')]
final class UnpaidController extends AbstractController
{
    #[Route(
        '/admin/order/unpaid',
        name: 'admin.order.unpaid',
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
    ): Response
    {

        $UnpaidStatusForm = $this
            ->createForm(
                type: UnpaidOrdersForm::class,
                data: $statusDTO = new UnpaidOrdersDTO(),
                options: ['action' => $this->generateUrl('orders-order:admin.order.unpaid')],
            )
            ->handleRequest($request);

        /**
         * TODO: Если у статуса нет упаковки - отправляем в начале на упаковку
         */

        $unsuccessful = [];
        $orders = [];

        if($UnpaidStatusForm->isSubmitted())
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

                $orderStatus = $orderStatusCollection->from('unpaid');

                $orderStatusDTO = new OrderStatusDTO(
                    $orderStatus,
                    $orderEvent->getId(),
                )
                    ->setProfile($this->getProfileUid())
                    ->setComment($orderEvent->getComment());

                /**
                 * Статус заказа можно двигать только вперед
                 */
                if($currentPriority >= $orderStatus->getOrderStatus()::priority())
                {
                    $unsuccessful[] = $orderEvent->getOrderNumber();
                    continue;
                }


                /**
                 * Невозможно применить статус без упаковки
                 */
                $isExistsStatusPackage = $existOrderEventByStatus
                    ->forOrder($order->getId())
                    ->forStatus(OrderStatusPackage::class)
                    ->isExists();

                if(false === $isExistsStatusPackage)
                {
                    $unsuccessful[] = $orderEvent->getOrderNumber();
                    continue;
                }


                /**
                 * Невозможно применить повторно статус
                 */
                $isExistsStatus = $existOrderEventByStatus
                    ->forOrder($order->getId())
                    ->forStatus($orderStatusDTO->getStatus())
                    ->isExists();

                if(true === $isExistsStatus)
                {
                    $unsuccessful[] = $orderEvent->getOrderNumber();
                    continue;
                }

                /**
                 * Изменить статус выполненного заказа невозможно
                 */
                $isExistsCompleted = $existOrderEventByStatus
                    ->forOrder($order->getId())
                    ->forStatus(OrderStatusCompleted::class)
                    ->isExists();

                if(true === $isExistsCompleted)
                {
                    $unsuccessful[] = $orderEvent->getOrderNumber();
                    continue;
                }

                /**
                 * Обновляем статус заказа
                 */

                $OrderStatusHandler = $handler->handle($orderStatusDTO);

                if(false === $OrderStatusHandler instanceof Order)
                {
                    $unsuccessful[] = $orderEvent->getOrderNumber();
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

                $orders[] = $orderEvent->getOrderNumber();
            }

            if(true === empty($unsuccessful))
            {
                return new JsonResponse(
                    [
                        'type' => 'success',
                        'header' => 'Статусы успешно обновлены',
                        'message' => 'Заказы #'.implode(', ', $orders),
                        'status' => 200,
                    ],
                    200,
                );
            }
        }

        if(false === empty($unsuccessful))
        {
            return new JsonResponse(
                [
                    'type' => 'danger',
                    'header' => 'Ошибка обновления заказов',
                    'message' => 'Заказы #'.implode(', ', $unsuccessful),
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
