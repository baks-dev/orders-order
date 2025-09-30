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
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\DeliveryTransport\BaksDevDeliveryTransportBundle;
use BaksDev\DeliveryTransport\Repository\Package\PackageOrderProducts\PackageOrderProductsInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Forms\Package\Orders\PackageOrdersOrderDTO;
use BaksDev\Orders\Order\Forms\Package\PackageOrdersDTO;
use BaksDev\Orders\Order\Forms\Package\PackageOrdersForm;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPackage;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierInterface;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductStocksTotalAccess\ProductStocksTotalAccessInterface;
use BaksDev\Products\Stocks\UseCase\Admin\Package\Orders\ProductStockOrderDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockHandler;
use BaksDev\Products\Stocks\UseCase\Admin\Package\Products\ProductStockDTO;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
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
        EntityManagerInterface $EntityManager,
        CurrentProductIdentifierInterface $CurrentProductIdentifier,
        PackageProductStockHandler $packageHandler,
        OrderStatusHandler $statusHandler,
        ?ProductStocksTotalAccessInterface $ProductStocksTotalAccess = null,
        ?PackageOrderProductsInterface $PackageOrderProducts = null,
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

            /** @var PackageOrdersOrderDTO $packageOrderDTO */
            foreach($packageOrdersDTO->getOrders() as $packageOrderDTO)
            {
                $order = $EntityManager->getRepository(Order::class)->find($packageOrderDTO->getId());

                if(false === ($order instanceof Order))
                {
                    $unsuccessful[] = $packageOrderDTO->getId();
                    continue;
                }

                $orderEvent = $EntityManager
                    ->getRepository(OrderEvent::class)
                    ->find($order->getEvent());

                if(false === ($orderEvent instanceof OrderEvent))
                {
                    $unsuccessful[] = $packageOrderDTO->getId();
                    continue;
                }

                $publish
                    ->addData(['order' => (string) $order->getId()])
                    ->addData(['profile' => (string) $this->getProfileUid()])
                    ->send('orders');

                $packageProductStockDTO = new PackageProductStockDTO();
                $orderEvent->getDto($packageProductStockDTO);
                $packageProductStockDTO->setProduct(new ArrayCollection());

                $packageProductStockDTO
                    ->getInvariable()
                    ->setUsr($this->getCurrentUsr())
                    ->setProfile($packageOrdersDTO->getProfile());


                /**
                 * Трансформируем идентификаторы продукта в константы
                 *
                 * @var OrderProduct $const
                 */
                foreach($orderEvent->getProduct() as $const)
                {
                    $ProductStockDTO = new ProductStockDTO();
                    $ProductStockDTO->setTotal($const->getTotal());

                    /** Получаем идентификаторы констант продукции  */
                    $currentProductIdentifierResult = $CurrentProductIdentifier
                        ->forEvent($const->getProduct())
                        ->forOffer($const->getOffer())
                        ->forVariation($const->getVariation())
                        ->forModification($const->getModification())
                        ->find();

                    $ProductStockDTO
                        ->setProduct($currentProductIdentifierResult->getProduct())
                        ->setOffer($currentProductIdentifierResult->getOfferConst())
                        ->setVariation($currentProductIdentifierResult->getVariationConst())
                        ->setModification($currentProductIdentifierResult->getModificationConst());

                    /** Проверяем наличие продукции с учетом резерва на любом складе */
                    if($ProductStocksTotalAccess && class_exists(BaksDevProductsStocksBundle::class))
                    {
                        // Метод возвращает общее количество ДОСТУПНОЙ продукции на всех складах (за вычетом резерва)
                        $isAccess = $ProductStocksTotalAccess
                            ->forProfile($packageOrdersDTO->getProfile())
                            ->forProduct($currentProductIdentifierResult->getProduct())
                            ->forOfferConst($currentProductIdentifierResult->getOfferConst())
                            ->forVariationConst($currentProductIdentifierResult->getVariationConst())
                            ->forModificationConst($currentProductIdentifierResult->getModificationConst())
                            ->get();

                        if($isAccess <= 0)
                        {
                            $this->addFlash(
                                'danger',
                                'danger.update',
                                'orders-order.admin',
                                'Недостаточное количество на складе',
                            );
                            return $this->redirectToReferer();
                        }
                    }

                    /** Проверяем, что все товары имеют параметры упаковки */
                    if($PackageOrderProducts && class_exists(BaksDevDeliveryTransportBundle::class))
                    {
                        /* Параметры упаковки товара */
                        $parameter = $PackageOrderProducts
                            ->product($currentProductIdentifierResult->getProduct())
                            ->offerConst($currentProductIdentifierResult->getOfferConst())
                            ->variationConst($currentProductIdentifierResult->getVariationConst())
                            ->modificationConst($currentProductIdentifierResult->getModificationConst())
                            ->find();

                        if(empty($parameter['size']) || empty($parameter['weight']))
                        {
                            // 'Для добавления товара в поставку необходимо указать параметры упаковки товара'
                            $this->addFlash(
                                'page.index',
                                'danger.size',
                                'delivery-transport.package',
                            );
                            return $this->redirectToReferer();
                        }
                    }

                    $packageProductStockDTO->addProduct($ProductStockDTO);

                }

                /**
                 * Создаем заявку на сборку заказа на "Целевой склад для упаковки заказа"
                 */
                // Присваиваем заявке склад для сборки
                $packageProductStockDTO
                    ->getInvariable()
                    ->setUsr($this->getCurrentUsr())
                    ->setProfile($packageOrdersDTO->getProfile())
                    ->setNumber($orderEvent->getOrderNumber());


                // Присваиваем заявке идентификатор заказа
                $productStockOrderDTO = new ProductStockOrderDTO();
                $productStockOrderDTO->setOrd($order->getId());

                $packageProductStockDTO->setNumber($orderEvent->getOrderNumber());
                $packageProductStockDTO->setOrd($productStockOrderDTO);


                /** @var PackageProductStockHandler $packageHandler */
                $PackageProductStock = $packageHandler->handle($packageProductStockDTO);

                if(false === ($PackageProductStock instanceof ProductStock))
                {
                    $this->addFlash(
                        'danger',
                        'danger.update',
                        'orders-order.admin',
                        $PackageProductStock,
                    );

                    return $this->redirectToReferer();
                }


                /**
                 * Обновляем статус заказа и присваиваем профиль склада упаковки.
                 */
                $OrderStatusDTO = new OrderStatusDTO(OrderStatusPackage::class, $order->getEvent());
                $OrderStatusDTO->setProfile($packageOrdersDTO->getProfile());


                /** @var OrderStatusHandler $statusHandler */
                $OrderStatusHandler = $statusHandler->handle($OrderStatusDTO);

                if(false === ($OrderStatusHandler instanceof Order))
                {
                    /* В случае ошибки удаляем заявку на упаковку */
                    $EntityManager->remove($PackageProductStock);
                    $EntityManager->flush();

                    $this->addFlash('danger',
                        'danger.update',
                        'orders-order.admin',
                        $OrderStatusHandler,
                    );

                    return $this->redirectToReferer();
                }

                $ordersNumbers[] = $orderEvent->getOrderNumber();
            }


            if(true === empty($unsuccessful))
            {
                return new JsonResponse(
                    [
                        'type' => 'success',
                        'header' => 'Упаковка заказов',
                        'message' => 'Статусы заказов '.implode(',', $unsuccessful).' успешно обновлены',
                        'status' => 200,
                    ],
                    200,
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
