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

namespace BaksDev\Orders\Order\Repository\AllOrders;

use BaksDev\Orders\Order\Entity;
use BaksDev\Products\Category\Entity as EntityCategory;
use BaksDev\Products\Product\Entity as EntityProduct;

use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Switcher\SwitcherInterface;
use BaksDev\Core\Type\Locale\Locale;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AllOrdersQuery implements AllOrdersInterface
{
    
    private Connection $connection;
    
    //private SwitcherInterface $switcher;
    private Locale $locale;
    
    public function __construct(
      Connection $connection,
      TranslatorInterface $translator,
      //SwitcherInterface $switcher
    )
    {
        $this->connection = $connection;
        $this->locale = new Locale($translator->getLocale());
        //$this->switcher = $switcher;
    }
    
    public function get(SearchDTO $search) : QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        
        $qb->setParameter('local', $this->locale, Locale::TYPE);
        
        $qb->select('orders.event');
        $qb->from(Entity\Order::TABLE, 'orders');
    
        $qb->addSelect('order_event.created');
        $qb->join('orders', Entity\Event\OrderEvent::TABLE, 'order_event', 'order_event.id = orders.event');
        
        /* Стоимость заказа */
        $qb->addSelect('price.price');
        $qb->addSelect('price.currency');
        $qb->addSelect('price.total');
        $qb->join('orders', Entity\Price\OrderPrice::TABLE, 'price', 'price.event_id = orders.event');
        
        /* Модификатор */
        //$qb->addSelect('modify.mod_date');
        ///$qb->join('orders', Entity\Modify\Modify::TABLE, 'modify', 'modify.event_id = orders.event');
        
        /** Product */

        $qb->join('order_event', EntityProduct\Product::TABLE, 'product', 'product.id = order_event.product');
    
        $qb->addSelect('product.event as product_event');
        $qb->join('product', EntityProduct\Event\ProductEvent::TABLE, 'product_event', 'product_event.id = product.event');
        
        /* Торговые предложения */
        $qb->join(
          'product',
          EntityProduct\Offers\Offers::TABLE,
          'product_offers',
          'product_offers.event_id = product.event');
    
        /* ТП */
        $qb->addSelect('product_offers_offer.value as offer');
        $qb->join(
          'product',
          EntityProduct\Offers\Offer\Offer::TABLE,
          'product_offers_offer',
          'product_offers_offer.product_offers_id = product_offers.id AND product_offers_offer.id = order_event.offer');
    
    
        /* Свойство торгового предложения в категории */
        
        $qb->addSelect('category_offer.reference');
        $qb->join(
          'product_offers_offer',
          EntityCategory\Offers\Offers::TABLE,
          'category_offer',
          'category_offer.id = product_offers_offer.offer');
    
    
        $qb->addSelect('category_offer_trans.name as offer_name');
        $qb->join(
          'category_offer',
          EntityCategory\Offers\Trans\Trans::TABLE,
          'category_offer_trans',
          'category_offer_trans.offer_id = category_offer.id AND category_offer_trans.local = :local');
    
    
    
    
        /* АРТИКУЛ C ФОТО */
        $qb->addSelect('product_offers_offer_article.value as offer_value');
        $qb->addSelect('product_offers_offer_article.article as offer_article');
        $qb->leftJoin(
          'product',
          EntityProduct\Offers\Offer\Offer::TABLE,
          'product_offers_offer_article',
          'product_offers_offer_article.product_offers_id = product_offers.id AND product_offers_offer_article.article IS NOT NULL');
    
        $qb->addSelect('category_offer_article.reference as article_reference');
        $qb->join(
          'product_offers_offer_article',
          EntityCategory\Offers\Offers::TABLE,
          'category_offer_article',
          'category_offer_article.id = product_offers_offer_article.offer');
    
    
        $qb->addSelect('category_offer_article_trans.name as offer_article_name');
        $qb->join(
          'category_offer_article',
          EntityCategory\Offers\Trans\Trans::TABLE,
          'category_offer_article_trans',
          'category_offer_article_trans.offer_id = category_offer_article.id AND category_offer_article_trans.local = :local');
        
        
        
        
        
    
        /* ФОТО ТП с артикулом */
        $qb->addSelect('product_offer_images.name AS image_name');
        $qb->addSelect('product_offer_images.dir AS image_dir');
        $qb->addSelect('product_offer_images.ext AS image_ext');
        $qb->addSelect('product_offer_images.cdn AS image_cdn');
    
        $qb->leftJoin(
          'product_offers_offer_article',
          EntityProduct\Offers\Offer\Image\Image::TABLE,
          'product_offer_images',
          'product_offer_images.offer_id = product_offers_offer_article.id AND product_offer_images.root = true'
        );
        
        
        
        $qb->addSelect('product_trans.name');
        $qb->addSelect('product_trans.preview');
        $qb->join(
          'product_event',
          EntityProduct\Trans\Trans::TABLE,
          'product_trans',
          'product_trans.event_id = product_event.id AND product_trans.local = :local');
        
        /* Общее фото */
        $qb->addSelect('product_photo.name AS photo_name');
        $qb->addSelect('product_photo.dir AS photo_dir');
        $qb->addSelect('product_photo.ext AS photo_ext');
        $qb->addSelect('product_photo.cdn AS photo_cdn');
        
        $qb->leftJoin(
          'product_event',
          EntityProduct\Photo\Photo::TABLE,
          'product_photo',
          'product_photo.event_id = product_event.id AND product_photo.root = true'
        );
        
        /* Категория */
        $qb->join(
          'product_event',
          EntityProduct\Category\Category::TABLE,
          'product_event_category',
          'product_event_category.event_id = product_event.id AND product_event_category.root = true'
        );
    
        $qb->join(
          'product_event_category',
          EntityCategory\Category::TABLE,
          'category',
          'category.id = product_event_category.category'
        );
    
        $qb->addSelect('category_trans.name AS category_name');
    
        $qb->join(
          'category',
          EntityCategory\Trans\Trans::TABLE,
          'category_trans',
          'category_trans.event_id = category.event AND category_trans.local = :local');

        
        $qb->orderBy('order_event.created', 'DESC');
        
        return $qb;
    }
    
}