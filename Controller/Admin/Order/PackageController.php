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

namespace BaksDev\Orders\Order\Controller\Admin\Order;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\DeliveryTransport\BaksDevDeliveryTransportBundle;
use BaksDev\DeliveryTransport\Repository\Package\PackageOrderProducts\PackageOrderProductsInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusPackage;
use BaksDev\Orders\Order\UseCase\Admin\Package\PackageOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Package\PackageOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use BaksDev\Products\Stocks\Entity\Stock\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductStocksTotalAccess\ProductStocksTotalAccessInterface;
use BaksDev\Products\Stocks\UseCase\Admin\Moving\MovingProductStockHandler;
use BaksDev\Products\Stocks\UseCase\Admin\Moving\ProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Moving\ProductStockForm;
use BaksDev\Products\Stocks\UseCase\Admin\Package\Orders\ProductStockOrderDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockForm;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockHandler;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
    #[Route('/admin/order/package/{id}', name: 'admin.order.package', methods: ['GET', 'POST'])]
    public function package(
        Request $request,
        #[MapEntity] Order $Order,
        OrderStatusHandler $statusHandler,
        MovingProductStockHandler $movingHandler,
        PackageProductStockHandler $packageHandler,
        EntityManagerInterface $entityManager,
        CentrifugoPublishInterface $publish,
        ?PackageOrderProductsInterface $packageOrderProducts = null,
        ?ProductStocksTotalAccessInterface $productStocksTotalAccess = null,
    ): Response
    {

        // Отправляем сокет для скрытия заказа у других менеджеров

        $socket = $publish
            ->addData(['order' => (string) $Order->getId()])
            ->addData(['profile' => (string) $this->getCurrentProfileUid()])
            ->send('orders');

        if($socket->isError())
        {
            return new JsonResponse($socket->getMessage(), status: 500);
        }

        $OrderEvent = $entityManager->getRepository(OrderEvent::class)->find($Order->getEvent());

        if(!$OrderEvent)
        {
            throw new InvalidArgumentException('Page not found');
        }

        $PackageOrderDTO = new PackageOrderDTO($this->getUsr(), $this->getProfileUid());
        //$PackageOrderDTO->setProfile($this->getProfileUid());
        $OrderEvent->getDto($PackageOrderDTO);


        /**
         * Делаем проверку, что заказ не отменен автоматически
         */
        if($PackageOrderDTO->getStatus()->equals(OrderStatus\OrderStatusCanceled::class))
        {
            return new JsonResponse(
                [
                    'type' => 'danger',
                    'header' => sprintf('Заказ #%s', $Order->getNumber()),
                    'message' => 'Заказ был отменен',
                    'status' => 500,
                ],
                500
            );
        }


        /**
         * Делаем проверку на отсутствие упаковки с данным заказом
         * на случай, если заказ уже кем-то отправлен на упаковку
         */
        $isExistsProductStockOrder = $entityManager
            ->getRepository(ProductStockOrder::class)
            ->findBy(['ord' => $Order->getId()]);

        if($isExistsProductStockOrder)
        {
            return new JsonResponse(
                [
                    'type' => 'danger',
                    'header' => sprintf('Заказ #%s', $Order->getNumber()),
                    'message' => 'Заказ уже отправлен на склад',
                    'status' => 500,
                ],
                500
            );
        }

        /**
         * Создаем складскую заявку на упаковку заказа
         */

        $form = $this
            ->createForm(
                type: PackageOrderForm::class,
                data: $PackageOrderDTO,
                options: ['action' => $this->generateUrl('orders-order:admin.order.package', ['id' => $Order->getId()])]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('package'))
        {
            $this->refreshTokenForm($form);

            /**
             * Отправляем сокет для скрытия заказа у других менеджеров
             */
            $socket = $publish
                ->addData(['order' => (string) $Order->getId()])
                ->addData(['profile' => (string) $this->getProfileUid()])
                ->send('orders');

            if($socket->isError())
            {
                return new JsonResponse($socket->getMessage(), status: 500);
            }

            $PackageProductStockDTO = new PackageProductStockDTO($this->getUsr()?->getId());
            $OrderEvent->getDto($PackageProductStockDTO);
            $PackageProductStockDTO->setProduct(new ArrayCollection());

            /* Трансформируем идентификаторы продукта в константы */

            ///* @var \BaksDev\Products\Stocks\UseCase\Admin\Package\Products\ProductStockDTO $const */
            foreach($PackageOrderDTO->getProduct() as $const)
            {

                $ProductStockDTO = new \BaksDev\Products\Stocks\UseCase\Admin\Package\Products\ProductStockDTO();

                $ProductStockDTO->setTotal($const->getPrice()->getTotal());

                $constProduct = $entityManager->getRepository(ProductEvent::class)->find($const->getProduct());

                if(!$constProduct)
                {
                    $this->addFlash('danger', 'danger.update', 'orders-order.admin');
                    return $this->redirectToReferer();
                }


                $ProductStockDTO->setProduct($constProduct->getMain());

                if($const->getOffer())
                {
                    $constOffer = $entityManager->getRepository(ProductOffer::class)->find($const->getOffer());
                    $ProductStockDTO->setOffer($constOffer?->getConst());
                }

                if($const->getVariation())
                {
                    $constVariation = $entityManager->getRepository(ProductVariation::class)->find($const->getVariation());
                    $ProductStockDTO->setVariation($constVariation?->getConst());
                }

                if($const->getModification())
                {
                    $constModification = $entityManager->getRepository(ProductModification::class)->find($const->getModification());
                    $ProductStockDTO->setModification($constModification?->getConst());
                }


                /** Проверяем наличие продукции с учетом резерва на любом складе */
                if($productStocksTotalAccess && class_exists(BaksDevProductsStocksBundle::class))
                {

                    // Метод возвращает общее количество ДОСТУПНОЙ продукции на всех складах (за вычетом резерва)
                    $isAccess = $productStocksTotalAccess
                        ->product($ProductStockDTO->getProduct())
                        ->offer($ProductStockDTO->getOffer())
                        ->variation($ProductStockDTO->getVariation())
                        ->modification($ProductStockDTO->getModification())
                        ->get();

                    if($isAccess <= 0)
                    {
                        $this->addFlash('danger', 'danger.update', 'orders-order.admin');
                        return $this->redirectToReferer();
                    }
                }

                /** Проверяем, что все товары имеют параметры упаковки */
                if($packageOrderProducts && class_exists(BaksDevDeliveryTransportBundle::class))
                {
                    /* Параметры упаковки товара */
                    $parameter = $packageOrderProducts->fetchParameterProductAssociative(
                        $ProductStockDTO->getProduct(),
                        $ProductStockDTO->getOffer(),
                        $ProductStockDTO->getVariation(),
                        $ProductStockDTO->getModification()
                    );

                    if(empty($parameter['size']) || empty($parameter['weight']))
                    {
                        // 'Для добавления товара в поставку необходимо указать параметры упаковки товара'
                        $this->addFlash('page.index', 'danger.size', 'delivery-transport.package');
                        return $this->redirectToReferer();
                    }
                }


                $PackageProductStockDTO->addProduct($ProductStockDTO);
            }


            /**
             * Создаем заявки на перемещение недостатка.
             * Заявка на перемещении создается раньше заявки на сборку, т.к. идет проверка при формировании путевого листа
             */
            $arrProductsMove = $request->request->all($form->getName())['product'];


            $MoveCollection = [];

            foreach($arrProductsMove as $product)
            {
                if(!empty($product['move']) && isset($product['move']['profile'], $product['move']['move']['destination']))
                {
                    $ord = (string) $Order->getId();
                    $warehouse = $product['move']['profile'];

                    $MoveProductStockDTO = new ProductStockDTO();
                    $MoveProductStockForm = $this->createForm(ProductStockForm::class, $MoveProductStockDTO);
                    $MoveProductStockForm->submit($product['move']);

                    //$MoveProductStockDTO->setProfile($this->getProfileUid());
                    $MoveProductStockDTO->setProfile(new UserProfileUid($warehouse));
                    $MoveProductStockDTO->getMove()->setOrd($Order->getId()); // присваиваем заказ
                    $MoveProductStockDTO->setNumber($Order->getNumber());

                    // Присваиваем идентификатор заказа
                    $MoveProductStockDTO->getOrd()->setOrd($Order->getId());

                    /*
                     * Группируем однотипные заявки
                     * Если заявка уже имеется - добавляем в указанную заявку продукцию для перемещения
                     */
                    if(isset($MoveCollection[$ord][$warehouse]))
                    {
                        /**
                         * Получаем заявку на указанный склад по ID заказа и добавляем продукцию.
                         *
                         * @var ProductStockDTO $AddMoveProductStockDTO
                         */
                        $AddMoveProductStockDTO = $MoveCollection[$ord][$warehouse];

                        foreach($AddMoveProductStockDTO->getProduct() as $addProduct)
                        {
                            $MoveProductStockDTO->addProduct($addProduct);
                        }
                    }

                    $MoveCollection[$ord][$warehouse] = $MoveProductStockDTO;
                }
            }

            foreach($MoveCollection as $movingCollection)
            {
                foreach($movingCollection as $moving)
                {
                    $MoveProductStock = $movingHandler->handle($moving);

                    if(!$MoveProductStock instanceof ProductStock)
                    {
                        $this->addFlash('danger', 'danger.update', 'orders-order.admin', $MoveProductStock);
                    }
                }
            }


            /**
             * Создаем заявку на сборку заказа на "Целевой склад для упаковки заказа"
             */

            // Присваиваем заявке склад для сборки
            $PackageProductStockForm = $this->createForm(PackageProductStockForm::class, $PackageProductStockDTO);
            $PackageProductStockForm->submit($request->request->all($form->getName()));
            $PackageProductStockDTO->setNumber($Order->getNumber());

            // Присваиваем заявке идентификатор заказа
            $ProductStockOrderDTO = new ProductStockOrderDTO();
            $ProductStockOrderDTO->setOrd($Order->getId());
            $PackageProductStockDTO->setOrd($ProductStockOrderDTO);

            /** @var PackageProductStockHandler $packageHandler */
            $PackageProductStock = $packageHandler->handle($PackageProductStockDTO);

            if(!$PackageProductStock instanceof ProductStock)
            {
                $this->addFlash('danger', 'danger.update', 'orders-order.admin', $PackageProductStock);
                return $this->redirectToReferer();
            }


            /**
             * Обновляем статус заказа и присваиваем профиль склада упаковки.
             */
            $OrderStatusDTO = new OrderStatusDTO(
                OrderStatusPackage::class,
                $Order->getEvent(),
            )
                ->setProfile($this->getProfileUid());

            //$OrderStatusDTO->setProfile($PackageProductStockDTO->getProfile());


            /** @var OrderStatusHandler $statusHandler */
            $OrderStatusHandler = $statusHandler->handle($OrderStatusDTO);

            if(!$OrderStatusHandler instanceof Order)
            {
                /* В случае ошибки удаляем заявку на упаковку */
                $entityManager->remove($PackageProductStock);
                $entityManager->flush();

                $this->addFlash('danger', 'danger.update', 'orders-order.admin', $OrderStatusHandler);
                return $this->redirectToReferer();
            }


            //return $this->redirectToRoute('orders-order:admin.index');

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

        return $this->render(['form' => $form->createView()]);
    }

    public function errorMessage(string $number, string $code): Response
    {
        return new JsonResponse(
            [
                'type' => 'danger',
                'header' => sprintf('Заказ #%s', $number),
                'message' => sprintf('Ошибка %s при обновлении заказа', $code),
                'status' => 500,
            ],
            500
        );
    }
}
