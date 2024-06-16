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

namespace BaksDev\Orders\Order\Repository\OrderDetail;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Delivery\Entity\Event\DeliveryEvent;
use BaksDev\Delivery\Entity\Price\DeliveryPrice;
use BaksDev\Delivery\Entity\Trans\DeliveryTrans;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\Delivery\Price\OrderDeliveryPrice;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\CategoryProductModificationTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Trans\CategoryProductVariationTrans;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Users\Address\Entity\GeocodeAddress;
use BaksDev\Users\Profile\TypeProfile\Entity\Section\Fields\Trans\TypeProfileSectionFieldTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\Section\Fields\TypeProfileSectionField;
use BaksDev\Users\Profile\TypeProfile\Entity\Trans\TypeProfileTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\TypeProfile;
use BaksDev\Users\Profile\UserProfile\Entity\Avatar\UserProfileAvatar;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Value\UserProfileValue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


final class OrderDetailRepository implements OrderDetailInterface
{

    private DBALQueryBuilder $DBALQueryBuilder;
    private ORMQueryBuilder $ORMQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        ORMQueryBuilder $ORMQueryBuilder,
    )
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
        $this->ORMQueryBuilder = $ORMQueryBuilder;
    }

    public function fetchDetailOrderAssociative(OrderUid $order): ?array
    {

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('orders.id AS order_id')
            ->addSelect('orders.event AS order_event')
            ->addSelect('orders.number AS order_number')
            ->from(Order::class, 'orders')
            ->where('orders.id = :order')
            ->setParameter('order', $order, OrderUid::TYPE);

        $dbal
            ->addSelect('event.status AS order_status')
            ->addSelect('event.created AS order_data')
            ->join(
                'orders',
                OrderEvent::class,
                'event',
                'event.id = orders.event'
            );


        $dbal->leftJoin(
            'orders',
            OrderUser::class,
            'order_user',
            'order_user.event = orders.event'
        );

        /* Продукция в заказе  */

        $dbal->leftJoin(
            'orders',
            OrderProduct::class,
            'order_product',
            'order_product.event = orders.event'
        );

        $dbal->leftJoin(
            'order_product',
            OrderPrice::class,
            'order_product_price',
            'order_product_price.product = order_product.id'
        );


        $dbal->leftJoin(
            'order_product',
            ProductEvent::class,
            'product_event',
            'product_event.id = order_product.product'
        );


        $dbal->leftJoin(
            'product_event',
            ProductInfo::class,
            'product_info',
            'product_info.product = product_event.main '
        );


        $dbal->leftJoin(
            'product_event',
            ProductTrans::class,
            'product_trans',
            'product_trans.event = product_event.id AND product_trans.local = :local'
        );

        /** Торговое предложение */
        $dbal->leftJoin(
            'product_event',
            ProductOffer::class,
            'product_offer',
            'product_offer.id = order_product.offer AND product_offer.event = product_event.id'
        );


        /** Тип торгового предложения */
        $dbal->leftJoin(
            'product_offer',
            CategoryProductOffers::class,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );

        /** Название торгового предложения */
        $dbal->leftJoin(
            'category_offer',
            CategoryProductOffersTrans::class,
            'category_offer_trans',
            'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        /** Множественный вариант */


        $dbal->leftJoin(
            'product_offer',
            ProductVariation::class,
            'product_variation',
            'product_variation.id = order_product.variation AND product_variation.offer = product_offer.id'
        );

        /* Получаем тип множественного варианта */

        $dbal->leftJoin(
            'product_variation',
            CategoryProductVariation::class,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );

        /* Получаем название множественного варианта */
        $dbal->leftJoin(
            'category_variation',
            CategoryProductVariationTrans::class,
            'category_variation_trans',
            'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
        );


        /* Получаем тип модификации множественного варианта */

        $dbal->leftJoin(
            'product_variation',
            ProductModification::class,
            'product_modification',
            'product_modification.id = order_product.modification AND product_modification.variation = product_variation.id'
        );

        $dbal->leftJoin(
            'product_modification',
            CategoryProductModification::class,
            'category_modification',
            'category_modification.id = product_modification.category_modification'
        );

        /* Получаем название типа модификации */
        $dbal->leftJoin(
            'category_modification',
            CategoryProductModificationTrans::class,
            'category_modification_trans',
            'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
        );


        /* Фото продукта */

        $dbal->leftJoin(
            'product_event',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_image',
            'product_offer_image.offer = product_offer.id AND product_offer_image.root = true'
        );

        $dbal->leftJoin(
            'product_variation',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        );

        $dbal->leftJoin(
            'product_modification',
            ProductModificationImage::class,
            'product_modification_image',
            'product_modification_image.modification = product_modification.id AND product_modification_image.root = true'
        );


        /** Категория продукта */


        /* Категория */
        $dbal->leftJoin(
            'product_event',
            ProductCategory::class,
            'product_event_category',
            'product_event_category.event = product_event.id AND product_event_category.root = true'
        );


        $dbal->leftJoin(
            'product_event_category',
            CategoryProduct::class,
            'category',
            'category.id = product_event_category.category'
        );


        $dbal->leftJoin(
            'category',
            CategoryProductTrans::class,
            'category_trans',
            'category_trans.event = category.event AND category_trans.local = :local'
        );

        $dbal->addSelect('category_info.url AS category_url');
        $dbal->leftJoin(
            'category',
            CategoryProductInfo::class,
            'category_info',
            'category_info.event = category.event'
        );


        $dbal->addSelect(
            "JSON_AGG
			( DISTINCT
				
					JSONB_BUILD_OBJECT
					(
						/* свойства для сортирвоки JSON */
						'product_id', order_product.id,
						'product_url', product_info.url,
						'product_article', product_info.article,
						'product_name', product_trans.name,
						
						'product_offer_reference', category_offer.reference,
						'product_offer_name', category_offer_trans.name,
						'product_offer_value', product_offer.value,
						'product_offer_postfix', product_offer.postfix,
						'product_offer_article', product_offer.article,
			
						
						'product_variation_reference', category_variation.reference,
						'product_variation_name', category_variation_trans.name,
						'product_variation_value', product_variation.value,
						'product_variation_postfix', product_variation.postfix,
						'product_variation_article', product_variation.article,
						
						'product_modification_reference', category_modification.reference,
						'product_modification_name', category_modification_trans.name,
						'product_modification_value', product_modification.value,
						'product_modification_postfix', product_modification.postfix,
						'product_modification_article', product_modification.article,
						
						'product_image', CASE
						                   WHEN product_modification_image.name IS NOT NULL THEN
                                                CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name)
                                           WHEN product_variation_image.name IS NOT NULL THEN
                                                CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name)
                                           WHEN product_offer_image.name IS NOT NULL THEN
                                                CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_image.name)
                                           WHEN product_photo.name IS NOT NULL THEN
                                                CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name)
                                           ELSE NULL
                                        END,
                                        
						'product_image_ext', CASE
						                        WHEN product_modification_image.name IS NOT NULL THEN
                                                    product_modification_image.ext
                                               WHEN product_variation_image.name IS NOT NULL THEN
                                                    product_variation_image.ext
                                               WHEN product_offer_image.name IS NOT NULL THEN
                                                    product_offer_image.ext
                                               WHEN product_photo.name IS NOT NULL THEN
                                                    product_photo.ext
                                               ELSE NULL
                                            END,
                                            
                        'product_image_cdn', CASE
                                                WHEN product_modification_image.name IS NOT NULL THEN
                                                    product_modification_image.cdn
                                               WHEN product_variation_image.name IS NOT NULL THEN
                                                    product_variation_image.cdn
                                               WHEN product_offer_image.name IS NOT NULL THEN
                                                    product_offer_image.cdn
                                               WHEN product_photo.name IS NOT NULL THEN
                                                    product_photo.cdn
                                               ELSE NULL
                                            END,

						'product_total', order_product_price.total,
						'product_price', order_product_price.price,
						'product_price_currency', order_product_price.currency,
						
						
						'category_name', category_trans.name,
						'category_url', category_info.url
		
					)
			
			)
			AS order_products"
        );


        /* Доставка */

        $dbal->leftJoin(
            'order_user',
            OrderDelivery::class,
            'order_delivery',
            'order_delivery.usr = order_user.id'
        );


        $dbal
            ->addSelect('order_delivery_price.price AS order_delivery_price')
            ->addSelect('order_delivery_price.currency AS order_delivery_currency')
            ->leftJoin(
                'order_delivery',
                OrderDeliveryPrice::class,
                'order_delivery_price',
                'order_delivery_price.delivery = order_delivery.id'
            );

        $dbal->leftJoin(
            'order_delivery',
            DeliveryEvent::class,
            'delivery_event',
            'delivery_event.id = order_delivery.event'
        );

        $dbal
            ->addSelect('delivery_trans.name AS delivery_name')
            ->leftJoin(
                'delivery_event',
                DeliveryTrans::class,
                'delivery_trans',
                'delivery_trans.event = order_delivery.event AND delivery_trans.local = :local'
            );

        $dbal
            ->addSelect('delivery_price.price AS delivery_price');

        $dbal->leftJoin(
            'delivery_event',
            DeliveryPrice::class,
            'delivery_price',
            'delivery_price.event = delivery_event.id'
        );

        /* Адрес доставки */

        $dbal->addSelect('delivery_geocode.longitude AS delivery_geocode_longitude');
        $dbal->addSelect('delivery_geocode.latitude AS delivery_geocode_latitude');
        $dbal->addSelect('delivery_geocode.address AS delivery_geocode_address');

        $dbal->leftJoin(
            'order_delivery',
            GeocodeAddress::class,
            'delivery_geocode',
            'delivery_geocode.latitude = order_delivery.latitude AND delivery_geocode.longitude = order_delivery.longitude'
        );


        /* Профиль пользователя */

        $dbal->leftJoin(
            'order_user',
            UserProfileEvent::class,
            'user_profile',
            'user_profile.id = order_user.profile'
        );

        $dbal->addSelect('user_profile_info.discount AS order_profile_discount');

        $dbal->leftJoin(
            'user_profile',
            UserProfileInfo::class,
            'user_profile_info',
            'user_profile_info.profile = user_profile.profile'
        );

        $dbal->leftJoin(
            'user_profile',
            UserProfileValue::class,
            'user_profile_value',
            'user_profile_value.event = user_profile.id'
        );

        $dbal->leftJoin(
            'user_profile',
            TypeProfile::class,
            'type_profile',
            'type_profile.id = user_profile.type'
        );

        $dbal->addSelect('type_profile_trans.name AS order_profile');
        $dbal->leftJoin(
            'type_profile',
            TypeProfileTrans::class,
            'type_profile_trans',
            'type_profile_trans.event = type_profile.event AND type_profile_trans.local = :local'
        );

        $dbal->leftJoin(
            'user_profile_value',
            TypeProfileSectionField::class,
            'type_profile_field',
            'type_profile_field.id = user_profile_value.field AND type_profile_field.card = true'
        );

        $dbal->leftJoin(
            'type_profile_field',
            TypeProfileSectionFieldTrans::class,
            'type_profile_field_trans',
            'type_profile_field_trans.field = type_profile_field.id AND type_profile_field_trans.local = :local'
        );

        /* Автарка профиля клиента */
        $dbal->addSelect("CONCAT ( '/upload/".$dbal->table(UserProfileAvatar::class)."' , '/', profile_avatar.name) AS profile_avatar_name");

        $dbal->addSelect('profile_avatar.ext AS profile_avatar_ext')
            ->addSelect('profile_avatar.cdn AS profile_avatar_cdn')
            ->leftJoin(
                'user_profile',
                UserProfileAvatar::class,
                'profile_avatar',
                'profile_avatar.event = user_profile.id'
            );


        /** Артикул продукта */

        //        $qb->addSelect("
        //					CASE
        //					   WHEN product_modification.article IS NOT NULL THEN product_modification.article
        //					   WHEN product_variation.article IS NOT NULL THEN product_variation.article
        //					   WHEN product_offer.article IS NOT NULL THEN product_offer.article
        //					   WHEN product_info.article IS NOT NULL THEN product_info.article
        //					   ELSE NULL
        //					END AS product_article
        //				"
        //        );

        $dbal->addSelect(
            "JSON_AGG
			( DISTINCT
				
					JSONB_BUILD_OBJECT
					(
						/* свойства для сортирвоки JSON */
						'0', type_profile_field.sort,

						'profile_type', type_profile_field.type,
						'profile_name', type_profile_field_trans.name,
						'profile_value', user_profile_value.value
					)
				
			)
			AS order_user"
        );


        $dbal->allGroupByExclude();

        return $dbal->fetchAssociative() ?: null;
    }

    public function getDetailOrder(OrderUid $order): mixed
    {
        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm->select('orders');
        $orm->from(Order::class, 'orders');
        $orm->where('orders.id = :order');
        $orm->setParameter('order', $order, OrderUid::TYPE);

        return $orm->enableCache('orders-order', 86400)->getOneOrNullResult();
    }
}
