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

namespace BaksDev\Orders\Order\UseCase\Admin\Package\Products;

use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\UseCase\Admin\Package\User\Delivery\OrderDeliveryDTO;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Stocks\Repository\ProductWarehouseTotal\ProductWarehouseTotalInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PackageOrderProductForm extends AbstractType
{
    public function __construct(
        private readonly UserByUserProfileInterface $userByUserProfile,
        private readonly ProductUserBasketInterface $info,
        private readonly ProductWarehouseTotalInterface $warehouseTotal
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


        /*  Перемещение */
        $builder->add('move', HiddenType::class);

        $builder->get('move')->addModelTransformer(
            new CallbackTransformer(
                function ($move) {
                    return $move instanceof Moving\MovingProductStockForm ? $move : null;
                },
                function ($move): void {}
            )
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options): void {
                /** @var PackageOrderProductDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                if($data)
                {

                    $warehouse = $options['warehouse'];

                    /** Получаем информацию о продукте */
                    $product = $this->info->fetchProductBasketAssociative(
                        $data->getProduct(),
                        $data->getOffer(),
                        $data->getVariation(),
                        $data->getModification()
                    );

                    if(!$product)
                    {
                        return;
                    }

                    $ProductUid = new ProductUid($product['id']);
                    $ProductOfferConst = $product['product_offer_const'] ? new ProductOfferConst($product['product_offer_const']) : null;
                    $ProductVariationConst = $product['product_variation_const'] ? new ProductVariationConst($product['product_variation_const']) : null;
                    $ProductModificationConst = $product['product_modification_const'] ? new ProductModificationConst($product['product_modification_const']) : null;
                    $totalStock = 0;

                    if($warehouse)
                    {
                        $totalStock = $this->warehouseTotal->getProductProfileTotal(
                            $warehouse,
                            $ProductUid,
                            $ProductOfferConst,
                            $ProductVariationConst,
                            $ProductModificationConst
                        );
                    }

                    $product['stock'] = $totalStock;
                    $data->setCard($product);

                    /** @var OrderDeliveryDTO $Delivery */
                    $Delivery = $form->getParent()?->getParent()?->getData()->getUsr()->getDelivery();


                    /* Если в заказе количество больше, чем в наличии на складе - добавляем перемещение */
                    if($Delivery->isPickup() === false && $data->getPrice()->getTotal() > $totalStock)
                    {

                        $UserUid = null;

                        if($warehouse)
                        {
                            $UserByUserProfile = $this->userByUserProfile->findUserByProfile($warehouse);
                            $UserUid = $UserByUserProfile?->getId();
                        }

                        $ProductMoving = new Moving\Products\ProductStockDTO($UserUid);
                        $ProductMoving->setProduct($ProductUid);
                        $ProductMoving->setOffer($ProductOfferConst);
                        $ProductMoving->setVariation($ProductVariationConst);
                        $ProductMoving->setModification($ProductModificationConst);

                        /** Недостаток */
                        $ProductMovingDiff = $data->getPrice()->getTotal() - $totalStock;
                        $ProductMoving->setTotal($ProductMovingDiff);

                        $Move = new Moving\MovingProductStockDTO();
                        $Move->getMove()->setDestination($warehouse);
                        $Move->addProduct($ProductMoving);

                        $data->setMove($Move);


                        $form->add(
                            'move',
                            Moving\MovingProductStockForm::class,
                            ['label' => false]
                        );

                    }
                }
            },
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PackageOrderProductDTO::class,
            'attr' => ['class' => 'order-basket'],
            'warehouse' => null,
            'usr' => null,

        ]);
    }
}
