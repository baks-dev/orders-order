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

namespace BaksDev\Orders\Order\Entity\Event;

use BaksDev\Orders\Order\Entity\Modify\OrderModify;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Price\OrderPrice;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

use BaksDev\Core\Entity\EntityEvent;
use Exception;
use InvalidArgumentException;

/* Event */

#[ORM\Entity]
#[ORM\Table(name: 'orders_event')]
#[ORM\Index(columns: ['product'])]
class OrderEvent extends EntityEvent
{
    public const TABLE = 'orders_event';
    
    /** ID */
    #[ORM\Id]
    #[ORM\Column(type: OrderEventUid::TYPE)]
    protected OrderEventUid $id;
    
    /** Профиль пользователя */
    #[ORM\Column(type: UserProfileUid::TYPE)]
    protected UserProfileUid $profile;
    
    /** ID заказа */
    #[ORM\Column(type: OrderUid::TYPE)]
    protected ?OrderUid $orders = null;
    
    /** Идентификатор продукта */
    #[ORM\Column(type: ProductUid::TYPE)]
    protected ProductUid $product;
    
    /** Торговое предложение */
    #[ORM\Column(type: ProductOfferUid::TYPE, nullable: true)]
    protected ?ProductOfferUid $offer = null;
    
    /** Стоимость покупки */
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: OrderPrice::class, cascade: ['all'])]
    protected OrderPrice $price;
    
    /** Дата заказа */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected DateTimeImmutable $created;
    
    /** Модификатор */
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: OrderModify::class, cascade: ['all'])]
    protected OrderModify $modify;
    
    public function __construct()
    {
        $this->id = new OrderEventUid();
        $this->price = new OrderPrice($this);
        $this->modify = new OrderModify($this);
    }
    
    public function __clone()
    {
        $this->id = new OrderEventUid();
    }
	
    public function getId() : OrderEventUid
    {
        return $this->id;
    }

    public function getOrders() : ?OrderUid
    {
        return $this->orders;
    }
	
    public function setOrders(OrderUid|Order $order) : void
    {
        $this->orders = $order instanceof Order ? $order->getId() : $order;
    }
    
	
    public function getDto($dto) : mixed
    {
        if($dto instanceof OrderEventInterface)
        {
            return parent::getDto($dto);
        }
        
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
    

    public function setEntity($dto) : mixed
    {
        if($dto instanceof OrderEventInterface)
        {
            return parent::setEntity($dto);
        }
        
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
    
}