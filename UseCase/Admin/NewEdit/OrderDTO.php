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

namespace BaksDev\Orders\Order\UseCase\Admin\NewEdit;


use BaksDev\Orders\Order\Entity\Event\EventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Category\Type\Id\CategoryUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderDTO implements EventInterface
{
    #[Assert\Uuid]
    private ?OrderEventUid $id = null;

    /** Идентификатор категории */
    #[Assert\Uuid]
    private CategoryUid $category;
    
    /** Идентификатор продукта */
    #[Assert\Uuid]
    private ProductUid $product;
    
    /** Торговое предложение */
    #[Assert\Uuid]
    private ?ProductOfferUid $offer = null;
    
    #[Assert\Valid]
    /* Стоимость заказа */
    private Price\PriceDTO $price;
    
    
    public function __construct() {
        $this->price = new Price\PriceDTO();
    }
    
    /* EVENT */
    public function getEvent() : ?OrderEventUid
    {
        return $this->id;
    }
    
    public function setId(OrderEventUid $id) : void
    {
        $this->id = $id;
    }
    
    /* PRODUCT */
    
    /**
     * @return ProductUid
     */
    public function getProduct() : ProductUid
    {
        return $this->product;
    }
    
    /**
     * @param ProductUid $product
     */
    public function setProduct(ProductUid $product) : void
    {
        $this->product = $product;
    }
    
    /* OFFER */
    
    /**
     * @return ProductOfferUid|null
     */
    public function getOffer() : ?ProductOfferUid
    {
        return $this->offer;
    }
    
    /**
     * @param ProductOfferUid|null $offer
     */
    public function setOffer(?ProductOfferUid $offer) : void
    {
        $this->offer = $offer;
    }
    
    /* PRICE */
    
    /**
     * @return Price\PriceDTO
     */
    public function getPrice() : Price\PriceDTO
    {
        return $this->price;
    }
    
    /**
     * @param Price\PriceDTO $price
     */
    public function setPrice(Price\PriceDTO $price) : void
    {
        $this->price = $price;
    }
    
    public function getPriceClass() : Price\PriceDTO
    {
        return new Price\PriceDTO();
    }

    /* CATEGORY */
    
    /**
     * @return CategoryUid
     */
    public function getCategory() : CategoryUid
    {
        return $this->category;
    }
    
    /**
     * @param CategoryUid $category
     */
    public function setCategory(CategoryUid $category) : void
    {
        $this->category = $category;
    }
    

}