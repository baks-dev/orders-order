<?php
/*
 * Copyright 2025.  Baks.dev <admin@baks.dev>
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

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\UseCase\Admin\Decommission\NewDecommissionOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Decommission\NewDecommissionOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\Decommission\NewDecommissionOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\Decommission\Products\NewDecommissionOrderProductDTO;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Repository\CurrentProductEvent\CurrentProductEventInterface;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByConstInterface;
use BaksDev\Products\Sign\UseCase\Admin\Decommission\DecommissionProductSignDTO;
use BaksDev\Products\Sign\UseCase\Admin\Decommission\DecommissionProductSignHandler;
use BaksDev\Products\Stocks\Entity\Total\ProductStockTotal;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;

#[AsController]
#[RoleSecurity('ROLE_ORDERS_DECOMMISSION')]
final class DecommissionController extends AbstractController
{
    #[Route('/admin/order/decommission/{id}', name: 'admin.decommission.new', methods: ['GET', 'POST'])]
    public function news(
        Request $request,
        #[MapEntity] ProductStockTotal $productStocksTotal,
        NewDecommissionOrderHandler $NewDecommissionOrderHandler,
        CurrentProductEventInterface $CurrentProductEventRepository,
        ProductDetailByConstInterface $productDetailByConst,
        DecommissionProductSignHandler $DecommissionProductSignHandler,
    ): Response
    {
        $orderDTO = new NewDecommissionOrderDTO();

        /** Product */
        $productEvent = $CurrentProductEventRepository->findByProduct($productStocksTotal->getProduct());
        if(false === $productEvent instanceof ProductEvent)
        {
            throw new InvalidArgumentException(sprintf(
                'Event of product %s does not exist',
                $productStocksTotal->getProduct())
            );
        }

        $productDetail = $productDetailByConst
            ->product($productStocksTotal->getProduct())
            ->offerConst($productStocksTotal->getOffer())
            ->variationConst($productStocksTotal->getVariation())
            ->modificationConst($productStocksTotal->getModification())
            ->findResult();

        $productDTO = new NewDecommissionOrderProductDTO()
            ->setProduct($productEvent->getId())
            ->setOffer($productDetail->getProductOfferUid())
            ->setVariation($productDetail->getProductVariationUid())
            ->setModification($productDetail->getProductModificationUid());

        $orderDTO->addProduct($productDTO);

        $orderDTO->getInvariable()->setProfile($this->getCurrentProfileUid())->setUsr($this->getCurrentUsr());

        $form = $this
            ->createForm(
                type: NewDecommissionOrderForm::class,
                data: $orderDTO,
                options: ['action' => $this->generateUrl(
                    'orders-order:admin.decommission.new',
                    ['id' => (string) $productStocksTotal]
                )],
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('new_decommission_order'))
        {
            $this->refreshTokenForm($form);

            $handle = $NewDecommissionOrderHandler->handle($orderDTO);

            $this->addFlash(
                'page.new',
                $handle instanceof Order ? 'success.new' : 'danger.new',
                'orders-order.admin',
                $handle instanceof Order ? $orderDTO->getInvariable()->getNumber() : $handle,
            );

            if(true === $handle instanceof Order &&true === $form->get('signs')->getData())
            {
                $DecommissionProductSignHandler->handle(new DecommissionProductSignDTO()
                    ->setUsr($this->getCurrentUsr())
                    ->setProfile($this->getCurrentProfileUid())
                    ->setTotal($form->get('product')->getData()->current()->getPrice()->getTotal())
                    ->setOffer($productDetail->getProductOfferConst())
                    ->setVariation($productDetail->getProductVariationConst())
                    ->setModification($productDetail->getProductModificationConst())
                    ->setProduct($productDetail->getId())
                    ->setOrd($handle->getId())
                );
            }

            return $handle instanceof Order ? $this->redirectToRoute('products-stocks:admin.total.index') : $this->redirectToReferer();
        }

        return $this->render([
            'form' => $form->createView(),
            'card' => $productDetail,
            'total' => $productStocksTotal->getTotal()
        ]);
    }
}