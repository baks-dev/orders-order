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

namespace BaksDev\Orders\Order\Controller\Admin;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Materials\Sign\Repository\GroupMaterialSignsByOrder\GroupMaterialSignsByOrderInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailByEvent\OrderDetailByEventInterface;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailResult;
use BaksDev\Orders\Order\Repository\OrderHistory\OrderHistoryInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\Services\ExistActiveServicePeriod\ExistActiveOrderServiceInterface;
use BaksDev\Orders\Order\Repository\Services\OneServiceById\OneServiceByIdInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusReturn;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCollection;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\Items\OrderProductItemDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Service\OrderServiceDTO;
use BaksDev\Products\Sign\Repository\AllProductSignByOrder\AllProductSignByOrderInterface;
use BaksDev\Products\Sign\Repository\GroupProductSignsByOrder\GroupProductSignsByOrderInterface;
use BaksDev\Products\Sign\Type\Status\ProductSignStatus\ProductSignStatusDone;
use BaksDev\Products\Sign\Type\Status\ProductSignStatus\ProductSignStatusProcess;
use BaksDev\Products\Sign\Type\Status\ProductSignStatus\ProductSignStatusReturn;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use BaksDev\Products\Stocks\Entity\Stock\Lock\ProductStockLock;
use BaksDev\Products\Stocks\Messenger\Lock\ProductStockLockMessage;
use BaksDev\Products\Stocks\Repository\ProductStocksByOrder\ProductStocksByOrderInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_HISTORY')]
final class EventDetailController extends AbstractController
{
    private string|null $error = null;

    #[Route('/admin/order/detail/event/{OrderEvent}', name: 'admin.event.detail', methods: ['GET', 'POST'])]
    public function index(
        #[Target('ordersOrderLogger')] LoggerInterface $logger,
        #[MapEntity] OrderEvent $OrderEvent,
        Request $request,
        CentrifugoPublishInterface $publish,
        MessageDispatchInterface $messageDispatch,
        CurrentOrderEventInterface $CurrentOrderEventRepository,
        ProductUserBasketInterface $userBasketRepository,
        OrderHistoryInterface $orderHistoryRepository,
        OneServiceByIdInterface $oneServiceByIdRepository,
        ExistActiveOrderServiceInterface $existActiveOrderServiceRepository,
        OrderStatusCollection $collection,
        EditOrderHandler $handler,
        OrderDetailByEventInterface $OrderDetailByEventRepository,
        ?GroupMaterialSignsByOrderInterface $GroupMaterialSignsByOrder = null,
        ?GroupProductSignsByOrderInterface $GroupProductSignsByOrder = null,
        ?AllProductSignByOrderInterface $allProductSignByOrderRepository = null,
        ?ProductStocksByOrderInterface $productStocksByOrderRepository = null,
    ): Response
    {
        $currentEvent = $CurrentOrderEventRepository
            ->forOrder($OrderEvent->getMain())
            ->find();

        if(false === $currentEvent)
        {
            throw new InvalidArgumentException(sprintf('Не удалось найти текущее событие для заказа %s', $OrderEvent->getMain()));
        }

        $isCurrent = $currentEvent->getId()->equals($OrderEvent->getId());


        /** Информация о заказе */
        $OrderInfo = $OrderDetailByEventRepository->find($OrderEvent->getId());

        if(false === ($OrderInfo instanceof OrderDetailResult))
        {
            return new Response('404 Page Not Found');
        }


        $OrderDTO = new EditOrderDTO($OrderEvent->getMain());
        $OrderEvent->getDto($OrderDTO);


        /**
         * Продукты в заказе
         *
         * @var OrderProductDTO $product
         */
        foreach($OrderDTO->getProduct() as $product)
        {
            $ProductUserBasketResult = $userBasketRepository
                ->forEvent($product->getProduct())
                ->forOffer($product->getOffer())
                ->forVariation($product->getVariation())
                ->forModification($product->getModification())
                ->profile($this->getProfileUid())
                ->find();

            $product->setCard($ProductUserBasketResult);
        }


        /**
         * Услуги в заказе
         */

        /** @var OrderServiceDTO $serv */
        foreach($OrderDTO->getServ() as $serv)
        {
            $serviceInfo = $oneServiceByIdRepository->find($serv->getServ());

            if(false === $serviceInfo)
            {
                return new Response('Информация об услуге в заказе не найдена');
            }

            $serv
                ->setName($serviceInfo->getName())
                ->setMoney($serv->getPrice()->getPrice())
                ->setMinPrice($serviceInfo->getPrice()->getValue());
        }


        /** Динамическая форма (необходима для динамического изменения полей в форме) - только при AJAX запросах */
        if(true === $request->isXmlHttpRequest())
        {
            $handleForm = $this->createForm(EditOrderForm::class, $OrderDTO);
            $handleForm->handleRequest($request);
        }


        // форма заказа
        $form = $this
            ->createForm(
                type: EditOrderForm::class,
                data: $OrderDTO,
                options: ['action' => $this->generateUrl(
                    'orders-order:admin.event.detail',
                    ['OrderEvent' => $OrderEvent->getId()]
                )],
            )
            ->handleRequest($request);

        if(false === $request->isXmlHttpRequest() && $form->isSubmitted() && false === $form->isValid())
        {

            $this->addFlash(
                'danger',
                'danger.update',
                'orders-order.admin',
            );

            return $this->redirectToReferer();
        }

        if($form->isSubmitted() && $form->isValid() && true === $isCurrent)
        {
            $this->refreshTokenForm($form);

            /**
             * Единицы продукции
             */
            foreach($OrderDTO->getProduct() as $product)
            {
                /** Еси при редактировании заказа был добавлен новый продукт - у него нет единиц продукции - нужно создать */
                if(true === $product->getItem()->isEmpty())
                {

                    /** Создаем единицы по общему количеству */
                    for($i = 0; $i < $product->getPrice()->getTotal(); $i++)
                    {
                        $OrderProductItemDTO = new OrderProductItemDTO();

                        $OrderProductItemDTO->getPrice()->setPrice($product->getPrice()->getPrice());
                        $OrderProductItemDTO->getPrice()->setCurrency($product->getPrice()->getCurrency());
                        $OrderProductItemDTO->generateConst();
                        $product->addItem($OrderProductItemDTO);
                    }
                }
            }

            /**
             * Проверка уникальности периода
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
                            $service->getPeriod()->getParams('time'),
                        ),
                    );

                    return $this->redirectToReferer();
                }
            }

            // Новый тип значения (строка) не совпадает с разрешенным типом параметра и может вводить ложные положительные значения, связанные с типами.

            $OrderHandle = $handler->handle($OrderDTO);

            if($OrderHandle instanceof Order)
            {
                $flash = $this->addFlash(
                    type: $OrderDTO->getOrderNumber(),
                    message: 'success.update',
                    domain: 'orders-order.admin',
                    status: 200,
                );
            }
            else
            {
                $flash = $this->addFlash(
                    $OrderDTO->getOrderNumber(),
                    'danger.update',
                    'orders-order.admin', $OrderHandle,
                    status: 400,
                );
            }

            /**
             * Синхронно блокируем складскую заявку
             *
             * @note при установленном модуле products-stocks у заказа должна быть созданная складская заявка
             */
            if(true === class_exists(BaksDevProductsStocksBundle::class))
            {
                /**
                 * Находим событие складской заявки связанной с заказом
                 */
                $ProductStockEventArray = $productStocksByOrderRepository
                    ->onOrder($OrderEvent->getMain())
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

            return $flash ?: $this->redirectToReferer();
        }


        /** История изменения статусов */
        $History = $orderHistoryRepository
            ->order($OrderEvent->getMain())
            ->findAllHistoryResult();


        /** Отправляем сокет для скрытия заказа у других менеджеров */
        $socket = $publish
            ->addData([
                'order' => (string) $OrderEvent->getMain(),
                'profile' => (string) $this->getCurrentProfileUid(),
                'context' => self::class.':'.__LINE__,
            ])
            ->send('orders');


        if($socket && $socket->isError())
        {
            return new JsonResponse($socket->getMessage());
        }


        /**
         * Получаем честные знаки на сырье
         */

        $MaterialSign = false;

        if(($GroupMaterialSignsByOrder instanceof GroupMaterialSignsByOrderInterface) && $isCurrent)
        {
            $MaterialSign = $GroupMaterialSignsByOrder
                ->forOrder($OrderEvent->getMain())
                ->findAll();

            if(false === $MaterialSign || false === $MaterialSign->valid())
            {
                $MaterialSign = false;
            }
        }


        $ProductSign = false;

        if(($GroupProductSignsByOrder instanceof GroupProductSignsByOrderInterface) && $isCurrent)
        {
            $ProductSign = $GroupProductSignsByOrder
                ->forOrder($OrderEvent->getMain())
                ->findAll();

            if(false === $ProductSign || false === $ProductSign->valid())
            {
                $ProductSign = false;
            }
        }


        /** Информация о Честных знаках по заказу */
        $ProductSignItems = false;

        if(($allProductSignByOrderRepository instanceof AllProductSignByOrderInterface) && $isCurrent)
        {
            $signStatus = match (true)
            {
                $OrderEvent->getStatus()->equals(OrderStatusCompleted::class) => ProductSignStatusDone::STATUS,
                $OrderEvent->getStatus()->equals(OrderStatusReturn::class) => ProductSignStatusReturn::STATUS,

                default => ProductSignStatusProcess::STATUS
            };

            $ProductSignItems = $allProductSignByOrderRepository
                ->forOrder($OrderEvent->getMain())
                ->forStatus($signStatus)
                ->findAll();
        }

        return $this->render(
            [
                'id' => (string) $OrderEvent->getMain(),
                'form' => $form->createView(),
                'order' => $OrderInfo,
                'history' => $History,
                'status' => $collection->from(($OrderInfo->getOrderStatus() ?? OrderStatusNew::STATUS)),
                'statuses' => $collection,
                'materials_sign' => $MaterialSign,
                'products_sign' => $ProductSign,
                'products_sign_items' => $ProductSignItems,
                'profile' => $this->getProfileUid(),
                'error' => $this->error,
                'is_current' => $isCurrent,
            ],
        );
    }
}
