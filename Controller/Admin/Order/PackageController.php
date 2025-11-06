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

declare (strict_types=1);

namespace BaksDev\Orders\Order\Controller\Admin\Order;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Forms\Package\Orders\PackageOrdersOrderDTO;
use BaksDev\Orders\Order\Forms\Package\PackageOrdersDTO;
use BaksDev\Orders\Order\Forms\Package\PackageOrdersForm;
use BaksDev\Orders\Order\Messenger\MultiplyOrdersPackage\MultiplyOrdersPackageMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\ExistOrderEventByStatus\ExistOrderEventByStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_STATUS')]
final class PackageController extends AbstractController
{
    /**
     * Упаковка (сборка) заказов
     */
    #[Route('/admin/order/package', name: 'admin.order.package', methods: ['GET', 'POST'])]
    public function package(
        Request $request,
        CentrifugoPublishInterface $publish,
        CurrentOrderEventInterface $currentOrderEventRepository,
        ExistOrderEventByStatusInterface $ExistOrderEventByStatusRepository,
        MessageDispatchInterface $messageDispatch,
        DeduplicatorInterface $deduplicator,
        OrderStatusHandler $OrderStatusHandler
    ): Response
    {
        $packageOrdersDTO = new PackageOrdersDTO();

        $packageOrdersForm = $this
            ->createForm(
                PackageOrdersForm::class,
                $packageOrdersDTO,
                ['action' => $this->generateUrl('orders-order:admin.order.package')],
            )
            ->handleRequest($request);

        if(
            $packageOrdersForm->isSubmitted()
            && $packageOrdersForm->isValid()
            && $packageOrdersForm->has('package')
        )
        {
            $this->refreshTokenForm($packageOrdersForm);

            $unsuccessful = [];
            $ordersNumbers = [];

            $CurrentUserUid = $this->getCurrentUsr();

            /** @var PackageOrdersOrderDTO $packageOrderDTO */
            foreach($packageOrdersDTO->getOrders() as $packageOrderDTO)
            {
                /** Скрываем заказ у всех пользователей */
                $publish
                    ->addData(['order' => (string) $packageOrderDTO->getId()])
                    ->send('orders');

                $Deduplicator = $deduplicator
                    ->namespace('orders-order')
                    ->deduplication([
                        (string) $packageOrderDTO->getId(),
                        self::class,
                    ]);

                if($Deduplicator->isExecuted())
                {
                    continue;
                }

                $OrderEvent = $currentOrderEventRepository
                    ->forOrder($packageOrderDTO->getId())
                    ->find();

                if(false === ($OrderEvent instanceof OrderEvent))
                {
                    $unsuccessful[] = $OrderEvent->getOrderNumber();
                    continue;
                }

                /** Проверяем, что заказ не был отменен */
                $isExists = $ExistOrderEventByStatusRepository
                    ->forOrder($OrderEvent->getMain())
                    ->forStatus(OrderStatusCanceled::class)
                    ->isExists();

                if($isExists)
                {
                    $unsuccessful[] = $OrderEvent->getOrderNumber();
                    continue;
                }

                $ordersNumbers[] = $OrderEvent->getOrderNumber();


                /** Если заказ перенаправляется на другой склад - только указываем новый склад */
                if(
                    $OrderEvent->getOrderProfile() instanceof UserProfileUid
                    && false === $OrderEvent->getOrderProfile()->equals($packageOrdersDTO->getProfile())
                )
                {

                    $OrderStatusDTO = new OrderStatusDTO(
                        OrderStatusNew::class,
                        $OrderEvent->getId(),
                    );

                    /** Применяем новый профиль склада для сборки */
                    $OrderStatusDTO->getInvariable()->setProfile($packageOrdersDTO->getProfile());

                    $OrderEvent->getDto($OrderStatusDTO);

                    $OrderStatusDTO
                        ->addComment(sprintf('Важно! Заказ отправлен на сборку с другого магазина региона (%s)', $request->getHost()));

                    $Order = $OrderStatusHandler->handle(
                        command: $OrderStatusDTO,
                        deduplicator: false,
                    );

                    if(false === $Order instanceof Order)
                    {
                        $unsuccessful[] = $OrderEvent->getOrderNumber();
                    }

                    continue;
                }


                /**
                 * Отправляем заказ на упаковку через очередь сообщений
                 */

                $MultiplyOrdersPackageMessage = new MultiplyOrdersPackageMessage(
                    $OrderEvent->getMain(),
                    $packageOrdersDTO->getProfile(),
                    $CurrentUserUid, // передаем текущего пользователя
                );

                $messageDispatch->dispatch(
                    message: $MultiplyOrdersPackageMessage,
                    transport: 'orders-order',
                );

                $Deduplicator->save();
            }

            if(true === empty($unsuccessful))
            {
                return new JsonResponse(
                    [
                        'type' => 'success',
                        'header' => 'Упаковка заказов',
                        'message' => 'Статусы заказов '.implode(',', $ordersNumbers).' успешно обновлены',
                        'status' => 200,
                    ],
                    200,
                );
            }

            if(false === empty($ordersNumbers))
            {
                $this->addFlash('success',
                    'Заказы #'.implode(', ', $ordersNumbers),
                    'Статусы успешно обновлены',
                    'orders-order.admin',
                );
            }

            $this->addFlash(
                'page.package',
                'danger.package',
                'orders-order.admin',
                $unsuccessful,
            );

            return $this->redirectToReferer();
        }

        $prePackageOrdersForm = $this->createForm(
            PackageOrdersForm::class,
            $packageOrdersDTO,
            ['action' => $this->generateUrl('orders-order:admin.order.package')],
        );

        return $this->render(['form' => $prePackageOrdersForm->createView()]);
    }
}
