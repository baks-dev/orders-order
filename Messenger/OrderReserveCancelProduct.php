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

declare(strict_types=1);

namespace BaksDev\Orders\Order\Messenger;

use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCompleted;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductOfferVariationModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductOfferVariationQuantity;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Repository\CurrentQuantity\CurrentQuantityByEventInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Modification\CurrentQuantityByModificationInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Offer\CurrentQuantityByOfferInterface;
use BaksDev\Products\Product\Repository\CurrentQuantity\Variation\CurrentQuantityByVariationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class OrderReserveCancelProduct
{
	private EntityManagerInterface $entityManager;
	
	private CurrentQuantityByModificationInterface $quantityByModification;
	
	private CurrentQuantityByVariationInterface $quantityByVariation;
	
	private CurrentQuantityByOfferInterface $quantityByOffer;
	
	private CurrentQuantityByEventInterface $quantityByEvent;
	
	
	public function __construct(
		EntityManagerInterface $entityManager,
		CurrentQuantityByModificationInterface $quantityByModification,
		CurrentQuantityByVariationInterface $quantityByVariation,
		CurrentQuantityByOfferInterface $quantityByOffer,
		CurrentQuantityByEventInterface $quantityByEvent,
	)
	{
		$this->entityManager = $entityManager;
		$this->entityManager->clear();
		
		$this->quantityByModification = $quantityByModification;
		$this->quantityByVariation = $quantityByVariation;
		$this->quantityByOffer = $quantityByOffer;
		$this->quantityByEvent = $quantityByEvent;
	}
	
	
	/** Сообщение ставит продукцию в резерв  */
	public function __invoke(OrderMessage $message) : void
	{
		/* Получаем всю продукцию в заказе */
		
		/**
		 * Новое событие заказа
		 *
		 * @var OrderEvent $OrderEvent
		 */
		$OrderEvent = $this->entityManager->getRepository(OrderEvent::class)->find($message->getEvent());
		
		/** Если статус не "ОТМЕНА" - завершаем обработчик */
		if($OrderEvent->getStatus()->getOrderStatusValue() !== OrderStatusCanceled::STATUS)
		{
			return;
		}
		
		if($message->getLast())
		{
			/**
			 * Предыдущее событие заказа
			 *
			 * @var OrderEvent $OrderEventLast
			 */
			$OrderEventLast = $this->entityManager->getRepository(OrderEvent::class)->find($message->getLast());
			
			/** Если статус предыдущего события "ВЫПОЛНЕН" - не снимаем резерв или наличие (просто удаляем)  */
			if($OrderEventLast->getStatus()->getOrderStatusValue() === OrderStatusCompleted::STATUS)
			{
				return;
			}
		}
		
		/** @var OrderProduct $product */
		foreach($OrderEvent->getProduct() as $product)
		{
			/** Снимаем резерв отмененного заказа */
			$this->changeReserve($product);
		}
	}
	
	
	public function changeReserve(OrderProduct $product) : void
	{
		$Quantity = null;
		
		/** Обновляем резерв модификации множественного варианта торгового предложения */
		if(!$Quantity && $product->getModification())
		{
			/** @var ProductOfferVariationModificationQuantity $Quantity */
			$Quantity = $this->quantityByModification->getModificationQuantity(
				$product->getProduct(),
				$product->getOffer(),
				$product->getVariation(),
				$product->getModification()
			);
		}
		
		/** Обновляем резерв множественного варианта торгового предложения */
		if(!$Quantity && $product->getVariation())
		{
			/** @var ProductOfferVariationQuantity $Quantity */
			$Quantity = $this->quantityByVariation->getVariationQuantity(
				$product->getProduct(),
				$product->getOffer(),
				$product->getVariation()
			);
		}
		
		/** Обновляем резерв торгового предложения */
		if(!$Quantity && $product->getOffer())
		{
			$Quantity = $this->quantityByOffer->getOfferQuantity(
				$product->getProduct(),
				$product->getOffer(),
			);
		}
		
		/** Обновляем резерв продукта */
		if(!$Quantity && $product->getProduct())
		{
			$Quantity = $this->quantityByEvent->getQuantity(
				$product->getProduct()
			);
		}
		
		if($Quantity)
		{
			$Quantity->subReserve($product->getTotal());
			$this->entityManager->flush();
		}
		
	}
	
}