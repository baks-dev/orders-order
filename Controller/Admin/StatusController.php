<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Controller\Admin;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[RoleSecurity('ROLE_ORDERS_STATUS')]
final class StatusController extends AbstractController
{
    #[Route(
        '/admin/order/status/{status}/{id}',
        name: 'admin.status',
        methods: ['GET'],
        condition: "request.headers.get('X-Requested-With') === 'XMLHttpRequest'",
    )]
    public function status(
        //Request $request,
        #[MapEntity] Entity\Order $Order,
        string $status,
        OrderStatus\Collection\OrderStatusCollection $orderStatusCollection,
        OrderStatusHandler $handler,
        CentrifugoPublishInterface $publish
    ): Response {
        /**
         * Обновляем статус заказа
         */
        $OrderStatus = $orderStatusCollection->from($status);
        $OrderStatusDTO = new OrderStatusDTO($OrderStatus, $Order->getEvent(), $this->getProfileUid());
        
        $OrderStatusHandler = $handler->handle($OrderStatusDTO);

        if (!$OrderStatusHandler instanceof Entity\Order)
        {
            // Отпарвляем сокет для скрытия заказа у других менеджеров
            $socket = $publish
                ->addData(['order' => (string) $OrderStatusHandler->getId()])
                ->addData(['profile' => (string) $this->getProfileUid()])
                ->send('orders');

            if ($socket->isError())
            {
                return new JsonResponse($socket->getMessage());
            }

            return new JsonResponse(
                [
                    'type' => 'danger',
                    'header' => 'Заказ #'.$Order->getNumber(),
                    'message' => 'Ошибка при обновлении заказа',
                    'status' => 400,
                ],
                400
            );
        }

        return new JsonResponse(
            [
                'type' => 'success',
                'header' => 'Заказ #'.$Order->getNumber(),
                'message' => 'Статус успешно обновлен',
                'status' => 200,
            ],
            200
        );
    }
}
