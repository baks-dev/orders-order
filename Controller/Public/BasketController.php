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

namespace BaksDev\Orders\Order\Controller\Public;

use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Orders\Order\Repository\Services\OneServiceById\OneServiceByIdInterface;
use BaksDev\Orders\Order\Repository\Services\OneServiceById\OneServiceByIdResult;
use BaksDev\Orders\Order\Type\OrderService\Service\ServiceUid;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Service\Price\OrderServicePriceDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\PublicOrderProductDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\OrderDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\OrderForm;
use BaksDev\Orders\Order\UseCase\Public\Basket\OrderHandler;
use BaksDev\Orders\Order\UseCase\Public\Basket\Service\BasketServiceDTO;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByEventInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierResult;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Services\Repository\AllServicesByProjectProfile\AllServicesByProjectProfileInterface;
use BaksDev\Users\Address\Services\GeocodeDistance;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileByRegion\UserProfileByRegionInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileByRegion\UserProfileByRegionResult;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;

#[AsController]
class BasketController extends AbstractController
{
    /** Корзина пользователя */
    private ?ArrayCollection $products = null;

    /** Массив разделенных заказов */
    private array|null $orders = null;

    #[Route('/basket', name: 'public.basket', priority: -100)]
    public function index(
        #[Target('ordersOrderLogger')] LoggerInterface $logger,
        Request $request,
        ProductUserBasketInterface $userBasket,
        OrderHandler $handler,
        AppCacheInterface $cache,
        GeocodeDistance $GeocodeDistance,
        UserProfileByRegionInterface $UserProfileByRegionRepository,
        OneServiceByIdInterface $oneServiceRepository,
        CurrentProductIdentifierByEventInterface $CurrentProductIdentifierByEventInterface,
        ?AllServicesByProjectProfileInterface $AllServicesByProjectProfile = null,
        #[MapQueryParameter] string|null $share = null,
        #[Autowire(env: 'PROJECT_USER')] string|null $projectUser = null,
        #[Autowire(env: 'PROJECT_PROFILE')] string|null $projectProfile = null,
        #[Autowire(env: 'HOST')] string|null $HOST = null,

    ): Response
    {

        $AppCache = $cache->init('orders-order-basket');
        $key = md5($HOST.$request->getClientIp().$request->headers->get('USER-AGENT'));

        if(false === is_null($share))
        {
            $key = $share;
        }

        $expires = 60 * 60; // Время кеширования 60 * 60 = 1 час

        if($this->getUsr())
        {
            $expires = 60 * 60 * 24; // Время кеширования 60 * 60 * 24 = 24 часа
        }

        /** Получаем корзину */
        if(true !== $AppCache->hasItem($key))
        {
            return $this->render(['form' => null]);
        }

        $this->products = ($AppCache->getItem($key))->get();

        if(true === empty($this->products))
        {
            $this->products = new ArrayCollection();
        }

        $OrderDTO = new OrderDTO();

        /** Присваиваем пользователя (клиента) */
        $OrderUserDTO = $OrderDTO->getUsr();
        $OrderUserDTO->setUsr($this->getUsr()?->getId() ?: new UserUid());

        // Получаем продукцию, добавленную в корзину и присваиваем актуальные значения
        if(false === $this->products->isEmpty())
        {
            $isRemove = false;

            /** @var PublicOrderProductDTO $product */
            foreach($this->products as $product)
            {
                /**
                 * Пробуем найти активные идентификаторы продукта на случай изменения
                 */
                $CurrentProductIdentifierResult = $CurrentProductIdentifierByEventInterface
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->find();

                if(false === ($CurrentProductIdentifierResult instanceof CurrentProductIdentifierResult))
                {
                    /**
                     * Удаляем из корзины, если карточка товара не найдена
                     *
                     * @var PublicOrderProductDTO $element
                     */

                    $predicat = static function($key, PublicOrderProductDTO $element) use ($product) {
                        return $element === $product;
                    };

                    $removeElement = $this->products->findFirst($predicat);

                    if($removeElement)
                    {
                        // Удаляем кеш
                        $AppCache->delete($key);

                        // Запоминаем новый кеш
                        $AppCache->get($key, function(ItemInterface $item) use ($removeElement, $expires) {
                            $item->expiresAfter($expires);
                            $this->products->removeElement($removeElement);

                            return $this->products;
                        });

                        $isRemove = $isRemove ?: true;

                        continue;
                    }
                }

                /**
                 * Присваиваем актуальные значения товара
                 */

                $product
                    ->setProduct($CurrentProductIdentifierResult->getEvent())
                    ->setOffer($CurrentProductIdentifierResult->getOffer())
                    ->setVariation($CurrentProductIdentifierResult->getVariation())
                    ->setModification($CurrentProductIdentifierResult->getModification());

                /** @var ProductUserBasketResult|false $ProductUserBasketResult */
                $ProductDetail = $userBasket
                    ->forEvent($CurrentProductIdentifierResult->getEvent())
                    ->forOffer($CurrentProductIdentifierResult->getOffer())
                    ->forVariation($CurrentProductIdentifierResult->getVariation())
                    ->forModification($CurrentProductIdentifierResult->getModification())
                    ->find();

                $product->setCard($ProductDetail);

                /** @var ProductUserBasketResult|false $ProductUserBasketResult */
                $ProductUserBasketResult = $userBasket
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->find();

                $OrderPriceDTO = $product->getPrice();
                $OrderPriceDTO
                    ->setPrice($ProductUserBasketResult->getProductPrice())
                    ->setCurrency($ProductUserBasketResult->getProductCurrency());

            }

            /**
             * Если произошло удаление - делаем редирект в корзину с сообщением
             */

            if(true === $isRemove)
            {
                $this->addFlash(
                    type: 'danger',
                    message: 'К сожалению произошли некоторые изменения в продукции %s. Убедитесь в стоимости товара и его наличии, и добавьте товар в корзину снова.',
                );

                return $this->redirectToReferer();
            }

            $OrderDTO->setProduct($this->products);
        }

        /* Данные по услугам */
        $has_services = ($AllServicesByProjectProfile instanceof AllServicesByProjectProfileInterface);

        if($has_services && false === $this->products->isEmpty())
        {
            $services = $AllServicesByProjectProfile->findAll();

            if(false === $services || false === $services->valid())
            {
                $has_services = false;
            }
            else
            {
                foreach($services as $serviceUId)
                {
                    /** @var OneServiceByIdResult $service */
                    $service = $oneServiceRepository->find($serviceUId);

                    if(false === ($service instanceof OneServiceByIdResult))
                    {
                        continue;
                    }

                    $BasketServiceDTO = new BasketServiceDTO();

                    $OrderServicePriceDTO = new OrderServicePriceDTO();
                    $OrderServicePriceDTO->setPrice(new Money($service->getPrice()->getValue()));

                    $BasketServiceDTO->setServ(new ServiceUid($serviceUId))
                        ->setPrice($OrderServicePriceDTO)
                        ->setMoney(new Money($service->getPrice()->getValue()))
                        ->setName($service->getName())
                        ->setPreview($service->getPreview());
                    $OrderDTO->addServ($BasketServiceDTO);
                }
            }
        }

        // Динамическая форма корзины
        $handleForm = $this->createForm(OrderForm::class, $OrderDTO);
        $handleForm->handleRequest($request);

        // Форма корзины
        $form = $this->createForm(
            type: OrderForm::class,
            data: $OrderDTO,
            options: ['action' => $this->generateUrl('orders-order:public.basket')]);


        if(null === $request->headers->get('X-Requested-With'))
        {
            $form->handleRequest($request);
        }

        if($form->isSubmitted() && $form->isValid())
        {
            //$this->refreshTokenForm($form);

            /** Делаем проверку геоданных */
            $OrderDeliveryDTO = $OrderUserDTO->getDelivery();
            $Latitude = $OrderDeliveryDTO->getLatitude();
            $Longitude = $OrderDeliveryDTO->getLongitude();

            if(false === ($Latitude instanceof GpsLatitude) || false === ($Longitude instanceof GpsLongitude))
            {
                $comment = 'Требуется уточнить адрес доставки!';

                if(!empty($OrderDTO->getComment()))
                {
                    $comment .= ' Комментарий клиента: '.$OrderDTO->getComment();
                }

                $OrderDTO->setComment($comment);
            }

            /**
             * Определяем ближайший к клиенту склад для упаковки заказа
             * без учета региональности
             */

            $OrderInvariable = $OrderDTO->getInvariable();
            $OrderInvariable->setUsr($projectUser);


            /** Если пользователь зарегистрирован - всегда присваиваем склад проекта для упаковки заказа */
            if($this->getUsr())
            {
                $OrderInvariable->setProfile($projectProfile);
            }

            /**
             * Если пользователь не авторизован - определяем ближайший к клиенту склад для упаковки заказа
             * без учета региональности
             */
            else
            {
                $profiles = $UserProfileByRegionRepository
                    ->onlyOrders()
                    ->findAll();

                if(true === $profiles->valid())
                {

                    if(
                        (false === ($Latitude instanceof GpsLatitude) || false === ($Longitude instanceof GpsLongitude))
                        && empty($projectProfile)
                    )
                    {
                        /**
                         * Если геоданные адреса доставки не определены
                         * и НЕ УКАЗАН профиль проекта - указываем координаты склада Current
                         *
                         * @var UserProfileByRegionResult $UserProfileByRegionResult
                         */
                        $UserProfileByRegionResult = $profiles->current();
                        $Latitude = $UserProfileByRegionResult->getLatitude();
                        $Longitude = $UserProfileByRegionResult->getLongitude();
                    }


                    $warehouse = null;

                    foreach($profiles as $profile)
                    {
                        if(
                            (false === ($Latitude instanceof GpsLatitude) || false === ($Longitude instanceof GpsLongitude))
                            && empty($projectProfile)
                        )
                        {
                            /**
                             * Если геоданные адреса доставки не определены
                             * УКАЗАН профиль проекта - указываем координаты склада проекта
                             *
                             * @var UserProfileByRegionResult $UserProfileByRegionResult
                             */

                            if(false === $profile->getId()->equals($projectProfile))
                            {
                                continue;
                            }

                            $Latitude = $profile->getLatitude();
                            $Longitude = $profile->getLongitude();
                        }


                        $distance = $GeocodeDistance
                            ->fromLatitude($profile->getLatitude())
                            ->fromLongitude($profile->getLongitude())
                            ->toLatitude($Latitude)
                            ->toLongitude($Longitude)
                            ->getDistanceRound();

                        $warehouse[$distance] = $profile->getId();
                    }

                    /** Если имеется склады, выбираем ближайший к клиенту */
                    if(false === is_null($warehouse))
                    {
                        $minDistance = min(array_keys($warehouse));
                        $OrderInvariable->setProfile($warehouse[$minDistance]);
                    }
                }
            }


            /**
             * Продукты
             */

            /** Постфикс для разделенных заказов */
            $orderNumberPostfix = 0;
            $orderNumber = $OrderDTO->getInvariable()->getNumber();

            foreach($OrderDTO->getProduct() as $product)
            {
                /** @var ProductUserBasketResult|array $ProductDetail */
                $ProductDetail = $product->getCard();

                /**
                 * Проверяем, что продукция в наличии в карточке
                 */

                if(
                    false === $ProductDetail->getProductEvent()->equals($ProductDetail->getCurrentProductEvent())
                    || $product->getPrice()->getTotal() > $ProductDetail->getProductQuantity()
                )
                {
                    /**
                     * Удаляем из корзины продукцию
                     *
                     * @var PublicOrderProductDTO $element
                     */
                    $predicat = static function($key, PublicOrderProductDTO $element) use ($product) {
                        return $element === $product;
                    };

                    $removeElement = $this->products->findFirst($predicat);

                    if($removeElement)
                    {
                        // Удаляем кеш
                        $AppCache->delete($key);

                        // Запоминаем новый кеш
                        $AppCache->get($key, function(ItemInterface $item) use ($removeElement, $expires) {
                            $item->expiresAfter($expires);
                            $this->products->removeElement($removeElement);

                            return $this->products;
                        });
                    }

                    $this->addFlash(
                        type: 'danger',
                        message: sprintf(
                            'К сожалению произошли некоторые изменения в продукции %s. Убедитесь в стоимости товара и его наличии, и добавьте товар в корзину снова.',
                            $ProductDetail->getProductName(),
                        ),
                    );

                    // Редирект на страницу товара
                    $postfix = (string) ($ProductDetail->getProductModificationPostfix() ?: $ProductDetail->getProductVariationPostfix() ?: $ProductDetail->getProductOfferPostfix() ?: null);

                    return $this->redirectToRoute('products-product:public.detail', [
                        'category' => $ProductDetail->getCategoryUrl(),
                        'url' => $ProductDetail->getProductUrl(),
                        'offer' => (string) $ProductDetail->getProductOfferValue(),
                        'variation' => (string) $ProductDetail->getProductVariationValue(),
                        'modification' => (string) $ProductDetail->getProductModificationValue(),
                        'postfix' => $postfix ? str_replace('/', '-', $postfix) : null,

                    ]);
                }

                /**
                 * Пытаемся разделить только если в заказе несколько продуктов
                 */

                if($OrderDTO->getProduct()->count() > 1)
                {

                    /**
                     * Делим заказ, если количество продукта превышает лимит - 100
                     */

                    /** Проверка лимита */
                    if($product->getPrice()->getTotal() > 100)
                    {
                        /**
                         * @var $OrderDTOClone OrderDTO
                         * Новый объект заказа и новой коллекцией с продуктом для разделения
                         */
                        $OrderDTOClone = clone $OrderDTO;

                        /** Новая коллекция с продуктом для разделения */
                        $filter = $OrderDTO->getProduct()->filter(function(
                            PublicOrderProductDTO $orderProduct
                        ) use (
                            $product
                        ) {
                            return $orderProduct->getProduct()->equals($product->getProduct())
                                && ((is_null($orderProduct->getOffer()) === true && is_null($product->getOffer()) === true) || $orderProduct->getOffer()?->equals($product->getOffer()))
                                && ((is_null($orderProduct->getVariation()) === true && is_null($product->getVariation()) === true) || $orderProduct->getVariation()?->equals($product->getVariation()))
                                && ((is_null($orderProduct->getModification()) === true && is_null($product->getModification()) === true) || $orderProduct->getModification()?->equals($product->getModification()));
                        });

                        /** Продолжаем создавать заказ даже при критической ошибке */
                        if(true === $filter->isEmpty())
                        {
                            $logger->critical(
                                message: 'Ошибка разделения заказа',
                                context: [self::class.':'.__LINE__],

                            );

                            $this->addFlash(
                                type: 'danger',
                                message: 'user.order.new.danger',
                                domain: 'user.order',
                                arguments: $OrderDTOClone->getInvariable()->getNumber(),
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

                        $handle = $handler->handle($OrderDTOClone);
                        $OrderDTO->getProduct()->removeElement($product);

                        /** Запоминаем успешно разделенный заказ */
                        if(true === $handle instanceof Order)
                        {
                            $this->orders[] = $handle->getId();
                        }

                        $this->addFlash(
                            type: $handle instanceof Order ? 'success' : 'danger',
                            message: $handle instanceof Order ? 'user.order.new.success' : 'user.order.new.danger',
                            domain: 'user.order',
                            arguments: $handle instanceof Order ? $OrderDTOClone->getInvariable()->getNumber() : $handle,
                        );
                    }
                }
            }

            /** Если при разделении удалили все продукты - завершаем создание заказа */
            if(true === $OrderDTO->getProduct()->isEmpty())
            {
                /** Если разделения были НЕ УСПЕШНЫ - редирект на basket */
                if(null === $this->orders)
                {
                    /** Если разделения были успешны - редирект на success */
                    return $this->redirectToRoute('orders-order:public.basket');
                }

                /** Если разделения были УСПЕШНЫ - редирект на success */

                $this->addFlash(
                    type: 'success',
                    message: 'user.order.new.success',
                    domain: 'user.order',
                    arguments: $OrderInvariable->getNumber(),
                );

                // Удаляем кеш
                $AppCache->delete($key);

                return $this->redirectToRoute(
                    route: 'orders-order:public.success',
                    parameters: ['id' => implode(',', $this->orders)],
                );
            }

            /** Если заказ был разделен - постфикс изменился при разделении - добавляем в номер заказа постфикс */
            if($orderNumberPostfix !== 0)
            {
                $orderNumberPostfix += 1;
                $OrderDTO->getInvariable()
                    ->setNumber($orderNumber.'-'.$orderNumberPostfix)
                    ->setPart($orderNumber); // номен партии - номер заказа без префикса
            }

            $Order = $handler->handle($OrderDTO);

            $this->addFlash(
                type: $Order instanceof Order ? 'success' : 'danger',
                message: $Order instanceof Order ? 'user.order.new.success' : 'user.order.new.danger',
                domain: 'user.order',
                arguments: $Order instanceof Order ? $OrderDTO->getInvariable()->getNumber() : $handle,
            );

            if($Order instanceof Order)
            {
                $this->orders[] = $Order->getId();

                // Удаляем кеш
                $AppCache->delete($key);

                return $this->redirectToRoute(
                    route: 'orders-order:public.success',
                    parameters: ['id' => implode(',', $this->orders)],
                );
            }
        }

        return $this->render([
            'form' => $form->createView(),
            'share' => $key,
            'is_shared' => empty($share) === false,
            'has_services' => $has_services,
        ]);
    }
}
