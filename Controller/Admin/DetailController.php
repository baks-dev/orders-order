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

namespace BaksDev\Orders\Order\Controller\Admin;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Sign\Repository\GroupMaterialSignsByOrder\GroupMaterialSignsByOrderInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\Repository\OrderHistory\OrderHistoryInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\Services\ExistActiveServicePeriod\ExistActiveOrderServiceInterface;
use BaksDev\Orders\Order\Repository\Services\OneServiceById\OneServiceByIdInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCollection;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Service\OrderServiceDTO;
use BaksDev\Products\Sign\Repository\GroupProductSignsByOrder\GroupProductSignsByOrderInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

#[AsController]
#[RoleSecurity('ROLE_ORDERS')]
final class DetailController extends AbstractController
{
    #[Route('/admin/order/detail/{id}', name: 'admin.detail', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        #[MapEntity] Order $Order,
        CurrentOrderEventInterface $currentOrderEventRepository,
        ProductUserBasketInterface $userBasketRepository,
        OrderDetailInterface $orderDetailRepository,
        OrderHistoryInterface $orderHistoryRepository,
        OneServiceByIdInterface $oneServiceByIdRepository,
        ExistActiveOrderServiceInterface $existActiveOrderServiceRepository,
        OrderStatusCollection $collection,
        EditOrderHandler $handler,
        CentrifugoPublishInterface $publish,
        string $id,

        ?GroupMaterialSignsByOrderInterface $GroupMaterialSignsByOrder = null,
        ?GroupProductSignsByOrderInterface $GroupProductSignsByOrder = null,

    ): Response
    {
        /** Получаем активное событие заказа */
        $OrderEvent = $currentOrderEventRepository
            ->forOrder($Order->getId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            throw new RouteNotFoundException('Page Not Found');
        }

        /** Информация о заказе */
        $OrderInfo = $orderDetailRepository->fetchDetailOrderAssociative($OrderEvent->getMain());

        if(!$OrderInfo)
        {
            return new Response('404 Page Not Found');
        }

        $OrderDTO = new EditOrderDTO($Order->getId());
        $OrderEvent->getDto($OrderDTO);

        /**
         * Продукты в заказе
         * @var OrderProductDTO $product
         */
        foreach($OrderDTO->getProduct() as $product)
        {
            $ProductUserBasketResult = $userBasketRepository
                ->forEvent($product->getProduct())
                ->forOffer($product->getOffer())
                ->forVariation($product->getVariation())
                ->forModification($product->getModification())
                ->find();

            $product->setCard($ProductUserBasketResult);
        }

        /**
         * Услуги в заказе
         */

        /** @var OrderServiceDTO $serv */
        foreach($OrderDTO->getServ() as $serv)
        {
            $serviceInfo = $oneServiceByIdRepository
                ->byProfile($this->getProfileUid())
                ->find($serv->getServ());

            if(false === $serviceInfo)
            {
                return new Response('Информация об услуге в заказе не найдена');
            }

            $serv
                ->setName($serviceInfo->getName())
                ->setMoney($serv->getPrice()->getPrice())
                ->setMinPrice($serviceInfo->getPrice()->getValue());
        }

        // Динамическая форма корзины (необходима для динамического изменения полей в форме)
        $handleForm = $this->createForm(EditOrderForm::class, $OrderDTO);
        $handleForm->handleRequest($request);

        // форма заказа
        $form = $this
            ->createForm(
                type: EditOrderForm::class,
                data: $OrderDTO,
                options: ['action' => $this->generateUrl('orders-order:admin.detail', ['id' => $id])]
            )
            ->handleRequest($request);

        if(false === $request->isXmlHttpRequest() && $form->isSubmitted() && false === $form->isValid())
        {

            $this->addFlash(
                'danger',
                'admin.danger.update',
                'orders-order.admin',
            );

            return $this->redirectToReferer();
        }

        if($form->isSubmitted() && $form->isValid())
        {

            $this->refreshTokenForm($form);

            /**
             * Проверка уникальности периода
             *
             * @var OrderServiceDTO $service
             */
            foreach($OrderDTO->getServ() as $service)
            {
                if(null === $service->getPeriod())
                {
                    continue;
                }

                $exist = $existActiveOrderServiceRepository
                    ->byDate($service->getDate())
                    ->byPeriod($service->getPeriod())
                    ->byEvent($OrderDTO->getEvent())
                    ->exist();

                if(true === $exist)
                {
                    $this->addFlash(
                        'danger',
                        sprintf('Услуга <b>"%s"</b> на <b>%s</b> в период <b>%s</b> УЖЕ ЗАБРОНИРОВАНА',
                            $service->getName(),
                            $service->getDate()->format('Y-m-d'),
                            $service->getPeriod()->getParams('time')
                        ),
                    );

                    return $this->redirectToReferer();
                }
            }

            $OrderHandler = $handler->handle($OrderDTO);

            if($OrderHandler instanceof Order)
            {
                $this->addFlash('success', 'success.update', 'orders-order.admin');
            }
            else
            {
                $this->addFlash('danger', 'danger.update', 'orders-order.admin', $OrderHandler);
            }

            return $this->redirectToReferer();
        }

        /** История изменения статусов */
        $History = $orderHistoryRepository
            ->order($OrderEvent->getMain())
            ->findAllHistory();

        // Отправляем сокет для скрытия заказа у других менеджеров
        $socket = $publish
            ->addData(['order' => (string) $OrderEvent->getMain()])
            ->addData(['profile' => (string) $this->getCurrentProfileUid()])
            ->send('orders');

        if($socket && $socket->isError())
        {
            return new JsonResponse($socket->getMessage());
        }

        /**
         * Получаем честные знаки на сырье
         */

        $MaterialSign = false;

        if($GroupMaterialSignsByOrder)
        {
            $MaterialSign = $GroupMaterialSignsByOrder
                ->forOrder($Order)
                ->findAll();

            if(false === $MaterialSign || false === $MaterialSign->valid())
            {
                $MaterialSign = false;
            }
        }

        $ProductSign = false;

        if($GroupProductSignsByOrder)
        {
            $ProductSign = $GroupProductSignsByOrder
                ->forOrder($Order)
                ->findAll();

            if(false === $ProductSign || false === $ProductSign->valid())
            {
                $ProductSign = false;
            }
        }


        return $this->render(
            [
                'id' => $id,
                'form' => $form->createView(),
                'order' => $OrderInfo,
                'history' => $History,
                'status' => $collection->from(($OrderInfo['order_status'] ?? 'new')),
                'statuses' => $collection,
                'materials_sign' => $MaterialSign,
                'products_sign' => $ProductSign
            ]
        );
    }
}
