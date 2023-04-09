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

namespace BaksDev\Orders\Order\Entity\Products;

use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Price\OrderPrice;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductOfferVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductOfferVariationModificationUid;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Entity\EntityState;
use App\System\Type\Locale\Locale;
use InvalidArgumentException;

/* Перевод OrderProduct */

#[ORM\Entity]
#[ORM\Table(name: 'orders_product')]
//#[ORM\Index(columns: ['name'])]
class OrderProduct extends EntityEvent
{
	public const TABLE = 'orders_product';
	
	/** ID */
	#[ORM\Id]
	#[ORM\Column(type: OrderProductUid::TYPE)]
	private OrderProductUid $id;
	
	/** Связь на событие */
	#[ORM\ManyToOne(targetEntity: OrderEvent::class, inversedBy: "product")]
	#[ORM\JoinColumn(name: 'event', referencedColumnName: "id")]
	private OrderEvent $event;
	
	/** Идентификатор События!!! продукта */
	#[ORM\Column(type: ProductEventUid::TYPE)]
	private ProductEventUid $product;
	
	/** Идентификатор торгового предложения */
	#[ORM\Column(type: ProductOfferUid::TYPE, nullable: true)]
	private ?ProductOfferUid $offer;
	
	/** Идентификатор множественного варианта торгового предложения */
	#[ORM\Column(type: ProductOfferVariationUid::TYPE, nullable: true)]
	private ?ProductOfferVariationUid $variation;
	
	/** Идентификатор модификации множественного варианта торгового предложения */
	#[ORM\Column(type: ProductOfferVariationModificationUid::TYPE, nullable: true)]
	private ?ProductOfferVariationModificationUid $modification;
	
	/** Стоимость покупки */
	#[ORM\OneToOne(mappedBy: 'product', targetEntity: Price\OrderPrice::class, cascade: ['all'])]
	private Price\OrderPrice $price;
	
	
	public function __construct(OrderEvent $event)
	{
		$this->id = new OrderProductUid();
		$this->event = $event;
	}
	
	
	public function __clone() : void
	{
		$this->id = new OrderProductUid();
	}
	
	
	public function getDto($dto) : mixed
	{
		if($dto instanceof OrderProductInterface)
		{
			return parent::getDto($dto);
		}
		
		throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
	}
	
	
	public function setEntity($dto) : mixed
	{
		
		if($dto instanceof OrderProductInterface)
		{
			return parent::setEntity($dto);
		}
		
		throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
	}
	
	

	public function getProduct() : ProductEventUid
	{
		return $this->product;
	}
	

	public function getOffer() : ?ProductOfferUid
	{
		return $this->offer;
	}

	public function getVariation() : ?ProductOfferVariationUid
	{
		return $this->variation;
	}

	public function getModification() : ?ProductOfferVariationModificationUid
	{
		return $this->modification;
	}
	
	public function getTotal() : int
	{
		return $this->price->getTotal();
	}
	
}