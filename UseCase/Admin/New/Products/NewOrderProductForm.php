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

namespace BaksDev\Orders\Order\UseCase\Admin\New\Products;

use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\UseCase\Admin\Package\User\Delivery\OrderDeliveryDTO;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Products\Stocks\Repository\ProductWarehouseTotal\ProductWarehouseTotalInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NewOrderProductForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        // Продукт

        $builder->add('product', HiddenType::class);

        $builder->get('product')->addModelTransformer(
            new CallbackTransformer(
                function ($product) {
                    return $product instanceof ProductEventUid ? $product->getValue() : $product;
                },
                function ($product) {
                    return new ProductEventUid($product);
                }
            )
        );

        // Торговое предложение

        $builder->add('offer', HiddenType::class);

        $builder->get('offer')->addModelTransformer(
            new CallbackTransformer(
                function ($offer) {
                    return $offer instanceof ProductOfferUid ? $offer->getValue() : $offer;
                },
                function ($offer) {
                    return $offer ? new ProductOfferUid($offer) : null;
                }
            )
        );

        // Множественный вариант

        $builder->add('variation', HiddenType::class);

        $builder->get('variation')->addModelTransformer(
            new CallbackTransformer(
                function ($variation) {
                    return $variation instanceof ProductVariationUid ? $variation->getValue() : $variation;
                },
                function ($variation) {
                    return $variation ? new ProductVariationUid($variation) : null;
                }
            )
        );

        // Модификация множественного варианта

        $builder->add('modification', HiddenType::class);

        $builder->get('modification')->addModelTransformer(
            new CallbackTransformer(
                function ($modification) {
                    return $modification instanceof ProductModificationUid ? $modification->getValue() : $modification;
                },
                function ($modification) {

                    return $modification ? new ProductModificationUid($modification) : null;
                }
            )
        );

        $builder->add('price', Price\NewOrderPriceForm::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewOrderProductDTO::class,
//            'attr' => ['class' => 'order-basket'],
//            'warehouse' => null,
//            'usr' => null,

        ]);
    }
}
