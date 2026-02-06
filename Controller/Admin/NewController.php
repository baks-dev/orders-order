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

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Orders\Order\Repository\Services\ExistActiveServicePeriod\ExistActiveOrderServiceInterface;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderForm;
use BaksDev\Orders\Order\UseCase\Admin\New\NewOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\New\Products\NewOrderProductDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Создание заказа в админке
 */
#[AsController]
#[RoleSecurity('ROLE_ORDERS_NEW')]
final class NewController extends AbstractController
{
    #[Route('/admin/order/new', name: 'admin.new', methods: ['GET', 'POST'])]
    public function news(
        #[Target('ordersOrderLogger')] LoggerInterface $logger,
        Request $request,
        NewOrderHandler $OrderHandler,
        ProductUserBasketInterface $userBasket,
        ExistActiveOrderServiceInterface $existActiveOrderServiceRepository,
    ): Response
    {
        $OrderDTO = new NewOrderDTO();

        /** Присваиваем заказу пользователя, который создал заказ */
        $OrderDTO->getInvariable()
            ->setUsr($this->getUsr()?->getId())
            ->setProfile($this->getProfileUid());

        // Форма
        $NewOrderForm = $this
            ->createForm(
                type: NewOrderForm::class,
                data: $OrderDTO,
                options: ['action' => $this->generateUrl('orders-order:admin.new'),],
            )
            ->handleRequest($request);

        $NewOrderForm->createView();

        if($NewOrderForm->isSubmitted() && $NewOrderForm->isValid() && $NewOrderForm->has('order_new'))
        {
            $this->refreshTokenForm($NewOrderForm);

            /** Если заказ без продукта и услуг */
            if(true === $OrderDTO->getProduct()->isEmpty() && true === $OrderDTO->getServ()->isEmpty())
            {
                $this->addFlash(
                    'page.new',
                    'Добавьте продукты или услуги в заказ',
                    'orders-order.admin',
                );

                return $this->redirectToRoute('orders-order:admin.index');
            }

            /**
             * Услуги
             */

            foreach($OrderDTO->getServ() as $service)
            {

                /** Проверка уникальности и активности */
                $exist = $existActiveOrderServiceRepository
                    ->byDate($service->getDate())
                    ->byPeriod($service->getPeriod())
                    ->exist();

                if(true === $exist)
                {
                    $this->addFlash(
                        'danger',
                        sprintf('Услуга <b>"%s"</b> на <b>%s</b> в выбранный период УЖЕ ЗАБРОНИРОВАНА',
                            $service->getName(),
                            $service->getDate()->format('Y-m-d'),
                        ),
                    );

                    return $this->redirectToReferer();
                }
            }

            /**
             * Продукты
             */

            /** Постфикс для разделенных заказов */
            $orderNumberPostfix = 0;
            $orderNumber = $OrderDTO->getInvariable()->getNumber();

            /** @var NewOrderProductDTO $product */
            foreach($OrderDTO->getProduct() as $product)
            {
                $ProductUserBasketResult = $userBasket
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->find();

                /** Редирект, если продукции не найдено */
                if(false === ($ProductUserBasketResult instanceof ProductUserBasketResult))
                {
                    return $this->redirectToRoute('orders-order:admin.index');
                }

                /**
                 * Присваиваем стоимость продукта в заказе
                 */

                /** С учетом персональной скидки из формы */
                $basketPrice = $ProductUserBasketResult->getProductPrice();

                if(false === empty($OrderDTO->getPreProduct()->getDiscount()))
                {
                    $basketPrice = $basketPrice->applyPercent($OrderDTO->getPreProduct()->getDiscount());
                }

                $product->getPrice()
                    ->setPrice($basketPrice)
                    ->setCurrency($ProductUserBasketResult->getProductCurrency());

                /** Пытаемся разделить только если в заказе несколько продуктов */
                if($OrderDTO->getProduct()->count() > 1)
                {
                    /**
                     * Делим заказ, если количество продукта превышает лимит - 100
                     */

                    /** Проверка лимита */
                    if($product->getPrice()->getTotal() > 100)
                    {
                        /** Новый объект заказа и новой коллекцией с продуктом для разделения */
                        $OrderDTOClone = clone $OrderDTO;

                        /** Новая коллекция с продуктом для разделения */
                        $filter = $OrderDTO->getProduct()->filter(function(
                            NewOrderProductDTO $orderProduct
                        ) use (
                            $product
                        ) {
                            return $orderProduct->getProduct()->equals($product->getProduct())
                                && ((is_null($orderProduct->getOffer()) === true && is_null($product->getOffer()) === true) || $orderProduct->getOffer()?->equals($product->getOffer()))
                                && ((is_null($orderProduct->getVariation()) === true && is_null($product->getVariation()) === true) || $orderProduct->getVariation()?->equals($product->getVariation()))
                                && ((is_null($orderProduct->getModification()) === true && is_null($product->getModification()) === true) || $orderProduct->getModification()?->equals($product->getModification()));
                        });

                        if(true === $filter->isEmpty())
                        {
                            $logger->critical(
                                message: 'Ошибка разделения заказа',
                                context: [self::class.':'.__LINE__],

                            );

                            $this->addFlash(
                                'page.new',
                                'danger.new',
                                'orders-order.admin',
                                $OrderDTOClone->getInvariable()->getNumber(),
                            );

                            continue;
                        }

                        /** Новый объект заказа и новой коллекцией с продуктом для разделения */
                        $OrderDTOClone->setProduct($filter);

                        /** Номер заказа с постфиксом разделения */
                        $orderNumberPostfix += 1;
                        $OrderDTOClone->getInvariable()
                            ->setNumber($orderNumber.'-'.$orderNumberPostfix)
                            ->setPart($orderNumber); // номен партии - номер заказа без префикса

                        $handle = $OrderHandler->handle($OrderDTOClone);
                        $OrderDTO->getProduct()->removeElement($product);

                        $this->addFlash(
                            'page.new',
                            $handle instanceof Order ? 'success.new' : 'danger.new',
                            'orders-order.admin',
                            $handle instanceof Order ? $OrderDTOClone->getInvariable()->getNumber() : $handle,
                        );
                    }
                }
            }

            /** Если при разделении удалили все продукты - завершаем создание заказа */
            if(true === $OrderDTO->getProduct()->isEmpty())
            {
                return $this->redirectToRoute('orders-order:admin.index');
            }

            /** Если заказ был разделен - добавляем в номер постфикс */
            if($orderNumberPostfix !== 0)
            {
                $orderNumberPostfix += 1;
                $OrderDTO->getInvariable()
                    ->setNumber($orderNumber.'-'.$orderNumberPostfix)
                    ->setPart($orderNumber); // номен партии - номер заказа без префикса
            }

            $handle = $OrderHandler->handle($OrderDTO);

            $this->addFlash(
                'page.new',
                $handle instanceof Order ? 'success.new' : 'danger.new',
                'orders-order.admin',
                $handle instanceof Order ? $OrderDTO->getInvariable()->getNumber() : $handle,
            );

            if($handle instanceof Order)
            {
                return $this->redirectToRoute('orders-order:admin.index');
            }

        }

        return $this->render(['form' => $NewOrderForm->createView()]);
    }
}