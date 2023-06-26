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
use BaksDev\Orders\Order\UseCase\Admin\Package\PackageOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Package\PackageOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductOfferVariationModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductOfferVariation;
use BaksDev\Products\Stocks\Entity\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductStocksMoveByOrder\ProductStocksMoveByOrderInterface;
use BaksDev\Products\Stocks\UseCase\Admin\Moving\MovingProductStockHandler;
use BaksDev\Products\Stocks\UseCase\Admin\Moving\ProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Moving\ProductStockForm;
use BaksDev\Products\Stocks\UseCase\Admin\Package\Orders\ProductStockOrderDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockForm;
use BaksDev\Products\Stocks\UseCase\Admin\Package\PackageProductStockHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[RoleSecurity('ROLE_ORDERS_STATUS')]
final class PackageController extends AbstractController
{
    /** Упаковка (сборка) заказов */
    #[Route(
        '/admin/order/package/{id}',
        name: 'admin.package',
        methods: ['GET', 'POST'],
        //condition: "request.headers.get('X-Requested-With') === 'XMLHttpRequest'",
    )]
    public function status(
        Request $request,
        #[MapEntity] Entity\Order $Order,
        OrderStatusHandler $statusHandler,
        MovingProductStockHandler $movingHandler,
        PackageProductStockHandler $packageHandler,
        EntityManagerInterface $entityManager,
        CentrifugoPublishInterface $publish,
        ProductStocksMoveByOrderInterface $productStocksMoveByOrder,
    ): Response {
        // Отправляем сокет для скрытия заказа у других менеджеров

        $socket = $publish
            ->addData(['order' => (string) $Order->getId()])
            ->addData(['profile' => (string) $this->getProfileUid()])
            ->send('orders');

        if ($socket->isError())
        {
            return new JsonResponse($socket->getMessage());
        }

        $OrderEvent = $entityManager->getRepository(Entity\Event\OrderEvent::class)->find($Order->getEvent());

        /** Создаем заявку на сборку для склада */
        $PackageOrderDTO = new PackageOrderDTO();
        $OrderEvent->getDto($PackageOrderDTO);

        $form = $this->createForm(PackageOrderForm::class, $PackageOrderDTO, [
            'action' => $this->generateUrl('Orders:admin.package', ['id' => $Order->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->has('package'))
        {
            /** Отправляем сокет для скрытия заказа у других менеджеров */
            $socket = $publish
                ->addData(['order' => (string) $Order->getId()])
                ->addData(['profile' => (string) $this->getProfileUid()])
                ->send('orders');

            if ($socket->isError())
            {
                return new JsonResponse($socket->getMessage());
            }

            /**
             * Создаем заявки на перемещение недостатка.
             */
            $arrProductsMove = $request->request->all($form->getName())['product'];

            $MoveCollection = [];

            foreach ($arrProductsMove as $product)
            {
                if (!empty($product['move']) && isset($product['move']['warehouse'], $product['move']['move']['destination']))
                {
                    $MoveProductStockDTO = new ProductStockDTO();
                    $MoveProductStockForm = $this->createForm(ProductStockForm::class, $MoveProductStockDTO);
                    $MoveProductStockForm->submit($product['move']);

                    $MoveProductStockDTO->setProfile($this->getProfileUid());
                    $MoveProductStockDTO->getMove()->setOrd($Order->getId()); // присваиваем заказ
                    $MoveProductStockDTO->setNumber($Order->getNumber());

                    $ord = (string) $Order->getId();
                    $warehouse = $product['move']['warehouse'];

                    /*
                     * Группируем однотипные заявки
                     * Если заявка уже имеется - добавляем в указанную заявку продукцию для перемещения
                     */
                    if (isset($MoveCollection[$ord][$warehouse]))
                    {
                        /**
                         * Получаем заявку на указанный склад по ID заказа и добавляем продукцию.
                         *
                         * @var ProductStockDTO $AddMoveProductStockDTO
                         */
                        $AddMoveProductStockDTO = $MoveCollection[$ord][$warehouse];

                        foreach ($AddMoveProductStockDTO->getProduct() as $addProduct)
                        {
                            $MoveProductStockDTO->addProduct($addProduct);
                        }
                    }

                    $MoveCollection[$ord][$warehouse] = $MoveProductStockDTO;
                }
            }

            foreach ($MoveCollection as $movingCollection)
            {
                foreach ($movingCollection as $moving)
                {
                    $MoveProductStock = $movingHandler->handle($moving);

                    if (!$MoveProductStock instanceof ProductStock)
                    {
                        $this->addFlash('danger', 'admin.danger.update', 'admin.order', $MoveProductStock);
                    }
                }
            }

            /**
             * Создаем заявку на сборку заказа.
             */
            $PackageProductStockDTO = new PackageProductStockDTO();
            $OrderEvent->getDto($PackageProductStockDTO);

            /* Трансформируем идентификаторы продукта в константы */

            /** @var \BaksDev\Products\Stocks\UseCase\Admin\Package\Products\ProductStockDTO $const */
            foreach ($PackageProductStockDTO->getProduct() as $const)
            {
                $constProduct = $entityManager->getRepository(ProductEvent::class)->find($const->getProduct());
                $const->setProduct($constProduct->getProduct());

                if ($const->getOffer())
                {
                    $constOffer = $entityManager->getRepository(ProductOffer::class)->find($const->getOffer());
                    $const->setOffer($constOffer->getConst());
                }

                if ($const->getVariation())
                {
                    $constVariation = $entityManager->getRepository(ProductOfferVariation::class)->find($const->getVariation());
                    $const->setVariation($constVariation->getConst());
                }

                if ($const->getModification())
                {
                    $constModification = $entityManager->getRepository(ProductOfferVariationModification::class)->find($const->getModification());
                    $const->setModification($constModification->getConst());
                }
            }

            // Присваиваем заявке склад
            $PackageProductStockForm = $this->createForm(PackageProductStockForm::class, $PackageProductStockDTO);
            $PackageProductStockForm->submit($request->request->all($form->getName()));

            $PackageProductStockDTO->setProfile($this->getProfileUid());
            $PackageProductStockDTO->setNumber($Order->getNumber());

            // Присваиваем заявке идентификатор заказа
            $ProductStockOrderDTO = new ProductStockOrderDTO();
            $ProductStockOrderDTO->setOrd($Order->getId());
            $PackageProductStockDTO->setOrd($ProductStockOrderDTO);

            /** @var PackageProductStockHandler $packageHandler */
            $PackageProductStock = $packageHandler->handle($PackageProductStockDTO);

            if (!$PackageProductStock instanceof ProductStock)
            {
                $this->addFlash('danger', 'admin.danger.update', 'admin.order', $PackageProductStock);
                return $this->redirectToReferer();
            }

            /**
             * Обновляем статус заказа.
             */
            $OrderStatusDTO = new OrderStatusDTO(new OrderStatus(new OrderStatus\OrderStatusPackage()), $Order->getEvent(), $this->getProfileUid());
            /** @var OrderStatusHandler $statusHandler */
            $OrderStatusHandler = $statusHandler->handle($OrderStatusDTO);

            if (!$OrderStatusHandler instanceof Entity\Order)
            {
                /* В случае ошибки удаляем заявку на упаковку */
                $entityManager->remove($PackageProductStock);
                $entityManager->flush();

                $this->addFlash('danger', 'admin.danger.update', 'admin.order', $OrderStatusHandler);
                return $this->redirectToReferer();
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

        return $this->render(['form' => $form->createView()]);
    }

    public function errorMessage(string $number, string $code): Response
    {
        return new JsonResponse(
            [
                'type' => 'danger',
                'header' => sprintf('Заказ #%s', $number),
                'message' => sprintf('Ошибка %s при обновлении заказа', $code),
                'status' => 400,
            ],
            400
        );
    }
}
