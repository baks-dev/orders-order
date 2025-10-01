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
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\OrderDTO;
use BaksDev\Orders\Order\UseCase\Public\Basket\OrderForm;
use BaksDev\Orders\Order\UseCase\Public\Basket\OrderHandler;
use BaksDev\Orders\Order\UseCase\Public\Basket\Service\BasketServiceDTO;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Services\BaksDevServicesBundle;
use BaksDev\Services\Repository\AllServicesByProjectProfile\AllServicesByProjectProfileInterface;
use BaksDev\Users\Address\Services\GeocodeDistance;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileByRegion\UserProfileByRegionInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileByRegion\UserProfileByRegionResult;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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

    #[Route('/basket', name: 'public.basket', priority: -100)]
    public function index(
        Request $request,
        ProductUserBasketInterface $userBasket,
        OrderHandler $handler,
        AppCacheInterface $cache,
        GeocodeDistance $GeocodeDistance,
        UserProfileByRegionInterface $UserProfileByRegionRepository,
        OneServiceByIdInterface $oneServiceRepository,
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
            /** @var OrderProductDTO $product */
            foreach($this->products as $product)
            {
                /** @var ProductUserBasketResult|false $ProductUserBasketResult */
                $ProductDetail = $userBasket
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->find();

                if(false === $ProductDetail)
                {
                    /**
                     * Удаляем из корзины, если карточка товара не найдена
                     *
                     * @var OrderProductDTO $element
                     */

                    $predicat = function($key, OrderProductDTO $element) use ($product) {
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

                        return $this->redirectToReferer();
                    }
                }

                $product->setCard($ProductDetail);

                /** @var ProductUserBasketResult|false $ProductUserBasketResult */
                $ProductUserBasketResult = $userBasket
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->find();

                $OrderPriceDTO = $product->getPrice();
                $OrderPriceDTO->setPrice($ProductUserBasketResult->getProductPrice());
                $OrderPriceDTO->setCurrency($ProductUserBasketResult->getProductCurrency());

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
                        ->setName($service->getName());
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

            /**
             * Проверяем, что продукция в наличии в карточке
             */

            foreach($OrderDTO->getProduct() as $product)
            {
                /** @var ProductUserBasketResult|array $ProductDetail */
                $ProductDetail = $product->getCard();

                if(
                    false === $ProductDetail->getProductEvent()->equals($ProductDetail->getCurrentProductEvent()) ||
                    $product->getPrice()->getTotal() > $ProductDetail->getProductQuantity()
                )
                {
                    /**
                     * Удаляем из корзины продукцию
                     *
                     * @var OrderProductDTO $element
                     */
                    $predicat = static function($key, OrderProductDTO $element) use ($product) {
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
            }


            $Order = $handler->handle($OrderDTO);

            if($Order instanceof Order)
            {
                $this->addFlash(
                    type: 'success',
                    message: 'user.order.new.success',
                    arguments: 'user.order',
                );

                // Удаляем кеш
                $AppCache->delete($key);

                return $this->redirectToRoute(
                    route: 'orders-order:public.success',
                    parameters: ['id' => $Order->getId()],
                );
            }

            $this->addFlash(
                type: 'danger',
                message: 'user.order.new.danger',
                domain: 'user.order',
                arguments: $Order);
        }

        return $this->render([
            'form' => $form->createView(),
            'share' => $key,
            'is_shared' => empty($share) === false,
            'has_services' => $has_services,
        ]);
    }
}
