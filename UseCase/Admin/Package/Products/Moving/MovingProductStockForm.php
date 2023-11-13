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

namespace BaksDev\Orders\Order\UseCase\Admin\Package\Products\Moving;

use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Products\Stocks\Repository\ProductWarehouseChoice\ProductWarehouseChoiceInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MovingProductStockForm extends AbstractType
{
    private ProductWarehouseChoiceInterface $productWarehouseChoice;

    //private WarehouseChoiceInterface $warehouseChoice;

    public function __construct(
        ProductWarehouseChoiceInterface $productWarehouseChoice,
        //WarehouseChoiceInterface $warehouseChoice
    ) {
        $this->productWarehouseChoice = $productWarehouseChoice;
        //$this->warehouseChoice = $warehouseChoice;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


//        $warehouses = $this->warehouseChoice->fetchAllWarehouse();
//
//        $builder->add(
//            'warehouse',
//            ChoiceType::class,
//            [
//                'choices' => $warehouses,
//                'choice_value' => function (?ContactsRegionCallConst $warehouse) {
//                    return $warehouse?->getValue();
//                },
//                'choice_label' => function (ContactsRegionCallConst $warehouse) {
//                    return $warehouse->getAttr().' ( '.$warehouse->getCounter().' )';
//                },
//
//                'label' => false
//            ]
//        );

        /* Константа Целевого склада */
//        $builder->add('warehouse', HiddenType::class);
//
//        $builder->get('warehouse')->addModelTransformer(
//            new CallbackTransformer(
//                function ($warehouse) {
//                    return $warehouse instanceof ContactsRegionCallConst ? $warehouse->getValue() : $warehouse;
//                },
//                function ($warehouse) {
//                    return new ContactsRegionCallConst($warehouse);
//                }
//            ),
//        );


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {
                /** @var MovingProductStockDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                //dd($data);

                if (!$data->getProduct()->isEmpty())
                {
                    /** @var Products\ProductStockDTO $product */
                    $product = $data->getProduct()->current();

                    $warehouses = [];

                    if($product->getUsr())
                    {
                        $warehouses = $this->productWarehouseChoice
                            ->fetchWarehouseByProduct(
                                $product->getUsr(),
                                $product->getProduct(),
                                $product->getOffer(),
                                $product->getVariation(),
                                $product->getModification()
                            );
                    }



                    // $data->getDestination()

                    $Destination = $data->getMove()->getDestination();

                    $warehouses = array_filter($warehouses, function ($v, $k) use ($Destination) {
                        return !$v->equals($Destination);
                    }, ARRAY_FILTER_USE_BOTH);

                    if ($warehouses)
                    {
                        $form->add(
                            'profile',
                            ChoiceType::class,
                            [
                                'choices' => $warehouses,
                                'choice_value' => function (?UserProfileUid $profile) {
                                    return $profile?->getValue();
                                },
                                'choice_label' => function (UserProfileUid $profile) {
                                    return $profile->getAttr().' ( '.$profile->getProperty().' )';
                                },

                                'label' => false
                            ]
                        );
                    }
                }
            }
        );

        /* Склад назначения при перемещении */
        $builder->add('move', Move\ProductStockMoveForm::class);

        /* Коллекция продукции  */
        $builder->add(
            'product',
            CollectionType::class,
            [
                'entry_type' => Products\ProductStockForm::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'by_reference' => false,
                'allow_delete' => true,
                'allow_add' => true,
                'prototype_name' => '__product__',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => MovingProductStockDTO::class,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
                'usr' => null
            ],
        );
    }
}
