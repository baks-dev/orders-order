<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Repository\ProductUserBasket;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\CategoryProductModificationTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\Trans\CategoryProductVariationTrans;
use BaksDev\Products\Category\Entity\Section\CategoryProductSection;
use BaksDev\Products\Category\Entity\Section\Field\CategoryProductSectionField;
use BaksDev\Products\Category\Entity\Section\Field\Trans\CategoryProductSectionFieldTrans;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Product\Entity\Active\ProductActive;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Property\ProductProperty;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;

final class ProductUserBasketRepository implements ProductUserBasketInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function fetchProductBasketAssociative(
        ProductEventUid $event,
        ?ProductOfferUid $offer = null,
        ?ProductVariationUid $variation = null,
        ?ProductModificationUid $modification = null,
    ): bool|array
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->addSelect('product_event.id AS event')
            ->addSelect('product_event.main AS id')
            ->from(ProductEvent::class, 'product_event')
            ->where('product_event.id = :event')
            ->setParameter('event', $event, ProductEventUid::TYPE);

        $dbal
            ->addSelect('product_active.active_from AS product_active_from')
            ->join(
                'product_event',
                ProductActive::class,
                'product_active',
                'product_active.event = product_event.id AND product_active.active = true AND product_active.active_from < NOW()
			
			AND (
				CASE
				   WHEN product_active.active_to IS NOT NULL 
				   THEN product_active.active_to > NOW()
				   ELSE TRUE
				END
			)
		'
            );

        $dbal
            ->addSelect('product.event AS current_event')
            ->leftJoin(
                'product_event',
                Product::class,
                'product',
                'product.id = product_event.main'
            );


        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product_event',
                ProductTrans::class,
                'product_trans',
                'product_trans.event = product_event.id AND product_trans.local = :local'
            );


        /** Базовая Цена товара */
        $dbal
            ->leftJoin(
                'product_event',
                ProductPrice::class,
                'product_price',
                'product_price.event = product_event.id'
            );

        /* ProductInfo */

        $dbal
            ->addSelect('product_info.url AS product_url')
            ->leftJoin(
                'product_event',
                ProductInfo::class,
                'product_info',
                'product_info.product = product_event.main '
            )->addGroupBy('product_info.article');


        /** Торговое предложение */

        $dbal
            ->addSelect('product_offer.id as product_offer_uid')
            ->addSelect('product_offer.const as product_offer_const')
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->leftJoin(
                'product_event',
                ProductOffer::class,
                'product_offer',
                'product_offer.event = product_event.id '.($offer ? ' AND product_offer.id = :product_offer' : '').' '
            )
            ->addGroupBy('product_offer.article');

        if($offer)
        {
            $dbal->setParameter('product_offer', $offer, ProductOfferUid::TYPE);
        }

        /** Цена торгового предложения */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferPrice::class,
            'product_offer_price',
            'product_offer_price.offer = product_offer.id'
        );


        /** Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference as product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );


        /** Получаем название торгового предложения */
        $dbal
            ->addSelect('category_offer_trans.name as product_offer_name')
            ->leftJoin(
                'category_offer',
                CategoryProductOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );

        /** Наличие и резерв торгового предложения */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferQuantity::class,
            'product_offer_quantity',
            'product_offer_quantity.offer = product_offer.id'
        );


        /**
         * Множественные варианты торгового предложения
         */

        $dbal
            ->addSelect('product_variation.id as product_variation_uid')
            ->addSelect('product_variation.const as product_variation_const')
            ->addSelect('product_variation.value as product_variation_value')
            ->addSelect('product_variation.postfix as product_variation_postfix')
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                'product_variation.offer = product_offer.id'.($variation ? ' AND product_variation.id = :variation' : '').' '
            );

        if($variation)
        {
            $dbal->setParameter('variation', $variation, ProductVariationUid::TYPE);
        }

        /** Цена множественного варианта */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_variation.id'
        );

        /** Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_variation.reference as product_variation_reference')
            ->leftJoin(
                'product_variation',
                CategoryProductVariation::class,
                'category_variation',
                'category_variation.id = product_variation.category_variation'
            );

        /** Получаем название множественного варианта */
        $dbal
            ->addSelect('category_variation_trans.name as product_variation_name')
            ->leftJoin(
                'category_variation',
                CategoryProductVariationTrans::class,
                'category_variation_trans',
                'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
            );

        /** Наличие и резерв множественного варианта */
        $dbal->leftJoin(
            'category_variation',
            ProductVariationQuantity::class,
            'product_variation_quantity',
            'product_variation_quantity.variation = product_variation.id'
        );


        /** Модификация множественного варианта торгового предложения */

        $dbal
            ->addSelect('product_modification.id as product_modification_uid')
            ->addSelect('product_modification.const as product_modification_const')
            ->addSelect('product_modification.value as product_modification_value')
            ->addSelect('product_modification.postfix as product_modification_postfix')
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'product_modification',
                'product_modification.variation = product_variation.id'.($modification ? ' AND product_modification.id = :modification' : '').' '
            );

        if($modification)
        {
            $dbal->setParameter('modification', $modification, ProductModificationUid::TYPE);
        }

        /** Цена модификации множественного варианта */
        $dbal->leftJoin(
            'product_modification',
            ProductModificationPrice::class,
            'product_modification_price',
            'product_modification_price.modification = product_modification.id'
        );

        /** Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_modification.reference as product_modification_reference')
            ->leftJoin(
                'product_modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = product_modification.category_modification'
            );

        /** Получаем название типа модификации */
        $dbal
            ->addSelect('category_modification_trans.name as product_modification_name')
            ->leftJoin(
                'category_modification',
                CategoryProductModificationTrans::class,
                'category_modification_trans',
                'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
            );

        /** Наличие и резерв модификации множественного варианта */
        $dbal->leftJoin(
            'category_modification',
            ProductModificationQuantity::class,
            'product_modification_quantity',
            'product_modification_quantity.modification = product_modification.id'
        );


        /** Артикул продукта */

        $dbal->addSelect('
            COALESCE(
                product_modification.article, 
                product_variation.article, 
                product_offer.article, 
                product_info.article
            ) AS product_article
		');


        /** ФОТО  */

        /* Фото модификаций */

        $dbal->leftJoin(
            'product_modification',
            ProductModificationImage::class,
            'product_modification_image',
            '
			product_modification_image.modification = product_modification.id AND product_modification_image.root = true
			'
        );


        /* Фото вариантов */

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            '
			product_variation_image.variation = product_variation.id AND 
			product_variation_image.root = true
			'
        );


        /* Фот оторговых предложений */

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            '
			
			product_offer_images.offer = product_offer.id AND 
			product_offer_images.root = true
			
		'
        );

        /* Фото продукта */

        $dbal->leftJoin(
            'product_offer',
            ProductPhoto::class,
            'product_photo',
            '
	
			product_photo.event = product_event.id AND product_photo.root = true
			'
        );


        $dbal
            ->addGroupBy('product_modification_image.ext')
            ->addGroupBy('product_modification_image.cdn')
            ->addGroupBy('product_modification_price.currency')
            ->addGroupBy('product_modification_quantity.reserve');

        $dbal
            ->addGroupBy('product_variation_image.ext')
            ->addGroupBy('product_variation_image.cdn')
            ->addGroupBy('product_variation_price.currency')
            ->addGroupBy('product_variation_quantity.reserve');

        $dbal
            ->addGroupBy('product_offer_images.ext')
            ->addGroupBy('product_offer_images.cdn')
            ->addGroupBy('product_offer_price.currency')
            ->addGroupBy('product_offer_quantity.reserve');

        $dbal
            ->addGroupBy('product_photo.ext')
            ->addGroupBy('product_photo.cdn')
            ->addGroupBy('product_price.currency')
            ->addGroupBy('product_price.reserve');


        $dbal->addSelect(
            "
			CASE
			   WHEN product_modification_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name, '/')
					
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name, '/')
			   
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name, '/')
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name, '/')
			   
			   ELSE NULL
			END AS product_image
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_modification_image.name IS NOT NULL THEN product_modification_image.ext
			   WHEN product_variation_image.name IS NOT NULL THEN product_variation_image.ext
			   WHEN product_offer_images.name IS NOT NULL THEN product_offer_images.ext
			   WHEN product_photo.name IS NOT NULL THEN product_photo.ext
			   ELSE NULL
			END AS product_image_ext
		");

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_modification_image.name IS NOT NULL THEN product_modification_image.cdn
			   WHEN product_variation_image.name IS NOT NULL THEN product_variation_image.cdn
			   WHEN product_offer_images.name IS NOT NULL THEN product_offer_images.cdn
			   WHEN product_photo.name IS NOT NULL THEN product_photo.cdn
			   ELSE NULL
			END AS product_image_cdn
		");


        /** Стоимость продукта */


        $dbal->addSelect(
            "
			CASE
			   WHEN product_modification_price.price IS NOT NULL AND product_modification_price.price > 0 
			   THEN product_modification_price.price
			   
			   WHEN product_variation_price.price IS NOT NULL AND product_variation_price.price > 0 
			   THEN product_variation_price.price
			   
			   WHEN product_offer_price.price IS NOT NULL AND product_offer_price.price > 0 
			   THEN product_offer_price.price
			   
			   WHEN product_price.price IS NOT NULL AND product_price.price > 0 
			   THEN product_price.price
			   
			   ELSE NULL
			END AS product_price
		"
        );

        /* Предыдущая стоимость продукта */

        $dbal->addSelect("
			COALESCE(
                NULLIF(product_modification_price.old, 0),
                NULLIF(product_variation_price.old, 0),
                NULLIF(product_offer_price.old, 0),
                NULLIF(product_price.old, 0),
                0
            ) AS product_old_price
		");


        /** Валюта продукта */

        $dbal->addSelect(
            "
			CASE
			   WHEN product_modification_price.price IS NOT NULL AND product_modification_price.price > 0 
			   THEN product_modification_price.currency
			   
			   WHEN product_variation_price.price IS NOT NULL AND product_variation_price.price > 0 
			   THEN product_variation_price.currency
			   
			   WHEN product_offer_price.price IS NOT NULL AND product_offer_price.price > 0 
			   THEN product_offer_price.currency
			   
			   WHEN product_price.price IS NOT NULL AND product_price.price > 0 THEN product_price.currency
			   ELSE NULL
			END AS product_currency
		"
        );

        /** Наличие продукта */

        $dbal->addSelect(
            "
			CASE

			    WHEN product_modification_quantity.quantity > 0 AND product_modification_quantity.quantity > product_modification_quantity.reserve 
			   THEN (product_modification_quantity.quantity - product_modification_quantity.reserve)

			   WHEN product_variation_quantity.quantity > 0 AND product_variation_quantity.quantity > product_variation_quantity.reserve  
			   THEN (product_variation_quantity.quantity - product_variation_quantity.reserve)
			
			   WHEN product_offer_quantity.quantity > 0 AND product_offer_quantity.quantity > product_offer_quantity.reserve 
			   THEN (product_offer_quantity.quantity - product_offer_quantity.reserve)

			   WHEN product_price.quantity > 0 AND product_price.quantity > product_price.reserve 
			   THEN (product_price.quantity - product_price.reserve)

			   ELSE 0
			END AS product_quantity
		"
        );

        //		->addGroupBy('product_modification_quantity.quantity')
        //		->addGroupBy('product_modification_quantity.reserve')

        /** Наличие */
        //		$dbal->addSelect("
        //			CASE
        //			   WHEN product_modification_price.price IS NOT NULL THEN product_modification_price.price
        //			   WHEN product_variation_price.price IS NOT NULL THEN product_variation_price.price
        //			   WHEN product_offer_price.price IS NOT NULL THEN product_offer_price.price
        //			   WHEN product_price.price IS NOT NULL THEN product_price.price
        //			   ELSE NULL
        //			END AS product_price
        //		"
        //		);


        /* Категория */
        $dbal->join(
            'product_event',
            ProductCategory::class,
            'product_event_category',
            'product_event_category.event = product_event.id AND product_event_category.root = true'
        );

        $dbal->join(
            'product_event_category',
            CategoryProduct::class,
            'category',
            'category.id = product_event_category.category'
        );

        $dbal->addSelect('category_trans.name AS category_name'); //->addGroupBy('category_trans.name');

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

        $dbal->leftJoin(
            'category',
            CategoryProductSection::class,
            'category_section',
            'category_section.event = category.event'
        );


        /** Свойства, участвующие в карточке */

        $dbal->leftJoin(
            'category_section',
            CategoryProductSectionField::class,
            'category_section_field',
            'category_section_field.section = category_section.id AND (category_section_field.card = TRUE )'
        );

        $dbal->leftJoin(
            'category_section_field',
            CategoryProductSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id AND 
            category_section_field_trans.local = :local'
        );

        $dbal->leftJoin(
            'category_section_field',
            ProductProperty::class,
            'product_property',
            'product_property.event = product_event.id AND 
            product_property.field = category_section_field.const'
        );


        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
			
				JSONB_BUILD_OBJECT
				(
				
					'0', category_section_field.sort, /* сортирвока */
				
					'field_uid', category_section_field.id,
					'field_const', category_section_field.const,
					'field_name', category_section_field.name,
					'field_alternative', category_section_field.alternative,
					'field_public', category_section_field.public,
					'field_card', category_section_field.card,
					'field_type', category_section_field.type,
					'field_trans', category_section_field_trans.name,
					'field_value', product_property.value
				)
			
		)
			AS category_section_field"
        );


        $dbal->allGroupByExclude();

        // Не кешируем результат для актуальной проверки наличия
        return $dbal->fetchAssociative();

    }

}
