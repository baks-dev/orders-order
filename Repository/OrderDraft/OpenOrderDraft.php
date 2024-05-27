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

namespace BaksDev\Orders\Order\Repository\OrderDraft;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Manufacture\Part\Entity\Products\ManufacturePartProduct;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusDraft;
use BaksDev\Products\Category\Entity\Offers\ProductCategoryOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\ProductCategoryOffersTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\ProductCategoryModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\ProductCategoryModificationTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\ProductCategoryVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Trans\ProductCategoryVariationTrans;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\Event\TypeProfileEvent;
use BaksDev\Users\Profile\TypeProfile\Entity\Trans\TypeProfileTrans;
use BaksDev\Users\Profile\TypeProfile\Entity\TypeProfile;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class OpenOrderDraft implements OpenOrderDraftInterface
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

    /**
     * Метод возвращает информацию об открытом черновике заказа
     */
    public function getOpenDraft(UserProfileUid $profile): bool|array
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(OrderEvent::class, 'ord_event')
            ->where('ord_event.profile = :profile')
            ->andWhere('ord_event.status = :status')
            ->setParameter('profile', $profile, UserProfileUid::TYPE)
            ->setParameter('status', OrderStatusDraft::STATUS);

        $dbal
            ->addSelect('ord.number AS order_number')
            ->addSelect('ord.id AS order_id')
            ->addSelect('ord.event AS order_event')
            ->join(
                'ord_event',
                Order::class,
                'ord',
                'ord.event = ord_event.id'
            );


        /** Тип заказа */

        $dbal
            //->addSelect('ord_usr.profile')
            ->leftJoin(
                'ord_event',
                OrderUser::class,
                'ord_usr',
                'ord_usr.event = ord_event.id'
            );

        $dbal
            //->addSelect('ord_profile_event.type AS profile_type')
            ->leftJoin(
                'ord_usr',
                UserProfileEvent::class,
                'ord_profile_event',
                'ord_profile_event.id = ord_usr.profile'
            );

        $dbal
            //->addSelect('ord_profile_type.type AS profile_type')
            ->leftJoin(
                'ord_profile_event',
                TypeProfile::class,
                'ord_profile_type',
                'ord_profile_type.id = ord_profile_event.type'
            );

        $dbal
            ->addSelect('type_profile_trans.name AS type_profile_name')
            ->leftJoin('ord_profile_type',
                TypeProfileTrans::class,
                'type_profile_trans',
                'type_profile_trans.event = ord_profile_type.event AND type_profile_trans.local = :local'
            );


        /** Ответственное лицо (Профиль пользователя) */

        $dbal
            //->addSelect('users_profile.event as users_profile_event')
            ->leftJoin(
                'ord_event',
                UserProfile::class,
                'users_profile',
                'users_profile.id = ord_event.profile'
            );

        $dbal
            ->addSelect('users_profile_personal.username AS users_profile_username')
            ->leftJoin(
                'users_profile',
                UserProfilePersonal::class,
                'users_profile_personal',
                'users_profile_personal.event = users_profile.event'
            );


        /**
         * Последний добавленный продукт
         */

        //$dbal->addSelect('part_product.total AS product_total');

        $dbal->leftOneJoin(
            'ord_event',
            OrderProduct::class,
            'part_product',
            'part_product.event = ord_event.id'
        );

        $dbal->addSelect('product_price.total AS product_total');
        $dbal->leftJoin(
            'part_product',
            OrderPrice::class,
            'product_price',
            'product_price.product = part_product.id'
        );


        $dbal->addSelect('product_event.id AS product_event');
        $dbal->leftJoin(
            'part_product',
            ProductEvent::TABLE,
            'product_event',
            'product_event.id = part_product.product'
        );

        $dbal->addSelect('product_trans.name AS product_name');
        $dbal->leftJoin(
            'product_event',
            ProductTrans::TABLE,
            'product_trans',
            'product_trans.event = product_event.id AND product_trans.local = :local'
        );

        /* Торговое предложение */

        $dbal->addSelect('product_offer.id as product_offer_uid');
        $dbal->addSelect('product_offer.value as product_offer_value');
        $dbal->addSelect('product_offer.postfix as product_offer_postfix');


        $dbal->leftJoin(
            'part_product',
            ProductOffer::TABLE,
            'product_offer',
            'product_offer.id = part_product.offer OR product_offer.id IS NULL'
        );

        /* Получаем тип торгового предложения */
        $dbal->addSelect('category_offer.reference AS product_offer_reference');
        $dbal->leftJoin(
            'product_offer',
            ProductCategoryOffers::TABLE,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );

        /* Получаем название торгового предложения */
        $dbal->addSelect('category_offer_trans.name as product_offer_name');
        $dbal->addSelect('category_offer_trans.postfix as product_offer_name_postfix');
        $dbal->leftJoin(
            'category_offer',
            ProductCategoryOffersTrans::TABLE,
            'category_offer_trans',
            'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        /* Множественные варианты торгового предложения */

        $dbal->addSelect('product_variation.id as product_variation_uid');
        $dbal->addSelect('product_variation.value as product_variation_value');
        $dbal->addSelect('product_variation.postfix as product_variation_postfix');

        $dbal->leftJoin(
            'part_product',
            ProductVariation::TABLE,
            'product_variation',
            'product_variation.id = part_product.variation OR product_variation.id IS NULL '
        );


        /* Получаем тип множественного варианта */
        $dbal->addSelect('category_variation.reference as product_variation_reference');
        $dbal->leftJoin(
            'product_variation',
            ProductCategoryVariation::TABLE,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );

        /* Получаем название множественного варианта */
        $dbal->addSelect('category_variation_trans.name as product_variation_name');

        $dbal->addSelect('category_variation_trans.postfix as product_variation_name_postfix');
        $dbal->leftJoin(
            'category_variation',
            ProductCategoryVariationTrans::TABLE,
            'category_variation_trans',
            'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
        );



        /* Модификация множественного варианта торгового предложения */

        $dbal->addSelect('product_modification.id as product_modification_uid');
        $dbal->addSelect('product_modification.value as product_modification_value');
        $dbal->addSelect('product_modification.postfix as product_modification_postfix');

        $dbal->leftJoin(
            'part_product',
            ProductModification::TABLE,
            'product_modification',
            'product_modification.id = part_product.modification OR product_modification.id IS NULL '
        );


        /* Получаем тип модификации множественного варианта */
        $dbal->addSelect('category_modification.reference as product_modification_reference');
        $dbal->leftJoin(
            'product_modification',
            ProductCategoryModification::TABLE,
            'category_modification',
            'category_modification.id = product_modification.category_modification'
        );

        /* Получаем название типа модификации */
        $dbal->addSelect('category_modification_trans.name as product_modification_name');
        $dbal->addSelect('category_modification_trans.postfix as product_modification_name_postfix');
        $dbal->leftJoin(
            'category_modification',
            ProductCategoryModificationTrans::TABLE,
            'category_modification_trans',
            'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
        );


        /* Фото продукта */

        $dbal->leftJoin(
            'product_event',
            ProductPhoto::TABLE,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductModificationImage::TABLE,
            'product_modification_image',
            'product_modification_image.modification = product_modification.id AND product_modification_image.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::TABLE,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        );


        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::TABLE,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        );

        $dbal->addSelect(
            "
			CASE
				WHEN product_modification_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductModificationImage::TABLE."' , '/', product_modification_image.name)
			   WHEN product_variation_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductVariationImage::TABLE."' , '/', product_variation_image.name)
			   WHEN product_offer_images.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductOfferImage::TABLE."' , '/', product_offer_images.name)
			   WHEN product_photo.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductPhoto::TABLE."' , '/', product_photo.name)
			   ELSE NULL
			END AS product_image
		"
        );

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
			    WHEN product_modification_image.name IS NOT NULL THEN
					product_modification_image.ext
			   WHEN product_variation_image.name IS NOT NULL THEN
					product_variation_image.ext
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.ext
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.ext
			   ELSE NULL
			END AS product_image_ext
		');

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
			   WHEN product_modification_image.name IS NOT NULL THEN
					product_modification_image.cdn			   
			    WHEN product_variation_image.name IS NOT NULL THEN
					product_variation_image.cdn
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.cdn
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.cdn
			   ELSE NULL
			END AS product_image_cdn
		');


        return $dbal
            ->enableCache('orders-order', 3600)
            ->fetchAssociative();
    }


    /**
     * Возвращает активное событие открытого черновика ответственного лица
     */
    public function findDraftEventOrNull(
        UserProfileUid $profile,
    ): ?OrderEvent
    {
        $dbal = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $dbal->select('event');
        $dbal->from(OrderEvent::class, 'event');

        $dbal
            ->andWhere('event.profile = :profile')
            ->setParameter('profile', $profile, UserProfileUid::TYPE);

        $dbal
            ->andWhere('event.status = :status')
            ->setParameter('status', OrderStatusDraft::STATUS);


        $dbal->join(
            Order::class,
            'ord',
            'WITH',
            'ord.event = event.id'
        );

        return $dbal->getOneOrNullResult();
    }

    public function existsOpenDraft(UserProfileUid $profile): bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(OrderEvent::class, 'event')
            ->where('event.profile = :profile')
            ->andWhere('event.status = :status')
            ->setParameter('profile', $profile, UserProfileUid::TYPE)
            ->setParameter('status', OrderStatusDraft::STATUS);

        $dbal
            ->andWhereExists(
                Order::class,
                'ord',
                'ord.event = event.id'
            );

        return $dbal
            ->fetchExist();
    }
}