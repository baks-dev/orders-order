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

namespace BaksDev\Orders\Order\Controller\User;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Status\OrderStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\UseCase\User\Basket\Add\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\User\Basket\OrderDTO;
use BaksDev\Orders\Order\UseCase\User\Basket\OrderForm;
use BaksDev\Orders\Order\UseCase\User\Basket\OrderHandler;
use BaksDev\Orders\Order\UseCase\User\Truncate\TruncateForm;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Currency\Type\CurrencyEnum;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use BaksDev\Users\Profile\UserProfile\Repository\CurrentUserProfile\CurrentUserProfileInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;

class BasketController extends AbstractController
{
	/* Корзина пользователя */
	
	private ?ArrayCollection $products = null;
	
	#[Route('/basket', name: 'user.basket')]
	public function index(
		Request $request,
		ProductUserBasketInterface $userBasket,
		OrderHandler $handler,
	
	) : Response
	{
		
		$cache = new ApcuAdapter();
		$key = 'basket.'.$request->getClientIp();
		$expires = 60 * 60; /* Время кешировния 60 * 60 = 1 час */
		
		if($this->getUser())
		{
			$expires = 60 * 60 * 24; /* Время кешировния 60 * 60 * 24 = 24 часа */
		}
		
		/* Получаем корзину */
		
		if($cache->hasItem($key))
		{
			$this->products = $cache->getItem($key)->get();
		}
		
		if($this->products === null)
		{
			$this->products = new ArrayCollection();
		}
		
		
		
		$OrderDTO = new OrderDTO();
		
		/** Присваиваем пользователя */
		$OrderUserDTO = $OrderDTO->getUsers();
		$OrderUserDTO->setUser($this->getUser()?->getId());
		//$OrderUserDTO->setProfile($this->getProfileUid());
		
		//dd($this->getUser());
		
		//dd($OrderUserDTO->getUserProfile()->setType());
		
		
		
		/** Получаем продукцию, добавленную в корзину и присваиваем актуальные значения */
		if(!$this->products->isEmpty())
		{
			/** @var OrderProductDTO $product */
			foreach($this->products as $product)
			{
			
				$ProductDetail = $userBasket->fetchProductBasketAssociative(
					$product->getProduct(),
					$product->getOffer(),
					$product->getVariation(),
					$product->getModification()
				);
				
				
				if($ProductDetail)
				{
					/** Если событие продукта изменилось - удаляем из корзины */
					if($ProductDetail['event'] !== $ProductDetail['current_event'])
					{
						
						/** @var OrderProductDTO $element */
						$predicat = function($key, OrderProductDTO $element) use ($product) {
							return $element === $product;
						};
						
						$removeElement = $this->products->findFirst($predicat);
						
						if($removeElement)
						{
							/* Удаляем кеш */
							$cache->delete($key);
							
							/** Запоминаем новый кеш */
							$cache->get($key, function(ItemInterface $item) use ($removeElement, $expires) {
								$item->expiresAfter($expires);
								$this->products->removeElement($removeElement);
								
								return $this->products;
							});
							
						}
						
						$this->addFlash('danger',
							sprintf('К сожалению произошли некоторые изменения в продукции %s. Убедителсь в стоимости товара и его наличии, и добавьте товар в корзину снова.',
								$ProductDetail['product_name']
							),
						);
						
						/** Редирект на страницу товара */
						return $this->redirectToRoute('Product:user.detail', [
							'url' => $ProductDetail['product_url'],
							'offer'=> $ProductDetail['product_offer_value'],
							'variation'=> $ProductDetail['product_variation_value'],
							'modification'=> $ProductDetail['product_modification_value']
						]);
					}
					
					
					
					$product->setCard($ProductDetail);
					
					$OrderPriceDTO = $product->getPrice();
					$OrderPriceDTO->setPrice(new Money(($ProductDetail['product_price'] / 100)));
					$OrderPriceDTO->setCurrency(new Currency(CurrencyEnum::from($ProductDetail['product_currency'])));
				}
				
				
				

			}
			
			$OrderDTO->setProduct($this->products);
		}
		
		/* Динамическая форма корзины */
		$handleForm = $this->createForm(OrderForm::class, $OrderDTO);
		$handleForm->handleRequest($request);
		
		/* Форма форма корзины */
		$form = $this->createForm(OrderForm::class, $OrderDTO, ['action' => $this->generateUrl('Orders:user.basket')]);
		
		if($request->headers->get('X-Requested-With') === null)
		{
			$form->handleRequest($request);
		}
		
		if($form->isSubmitted() && $form->isValid())
		{
			$Order = $handler->handle($OrderDTO);
			
			if($Order instanceof Order)
			{
				$this->addFlash('success', 'user.order.new.success', 'user.order');
				
				/* Удаляем кеш */
				$cache->delete($key);
				
				return $this->redirectToRoute('Orders:user.basket');
			}
			
			$this->addFlash('danger', 'user.order.new.danger', 'user.order', $Order);
			
			//return $this->redirectToRoute('Orders:user.basket');
		}
		
		return $this->render([
			'form' => $form->createView(),
		]);
		
	}
	
}
