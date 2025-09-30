<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Forms\Package;

use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Forms\Package\Orders\PackageOrdersOrderForm;
use BaksDev\Orders\Order\Forms\Package\Products\PackageOrdersProductDTO;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketInterface;
use BaksDev\Orders\Order\Repository\ProductUserBasket\ProductUserBasketResult;
use BaksDev\Products\Stocks\Repository\ProductWarehouseTotal\ProductWarehouseTotalInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileChoice\UserProfileChoiceInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use BaksDev\Orders\Order\Forms\Package\Products\PackageOrdersProductForm;

final class PackageOrdersForm extends AbstractType
{
    public function __construct(
        private readonly UserProfileChoiceInterface $userProfileChoice,
        private readonly UserProfileTokenStorageInterface $profileTokenStorage,
        private readonly ProductUserBasketInterface $ProductUserBasketRepository,
        private readonly EntityManagerInterface $EntityManager,
        private readonly ProductWarehouseTotalInterface $WarehouseTotal
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'orders',
            CollectionType::class,
            [
                'entry_type' => PackageOrdersOrderForm::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'allow_add' => true
            ]
        );

        /* Перемещаемая продукция */
        $builder->add('products', CollectionType::class, [
            'entry_type' => PackageOrdersProductForm::class,
            'entry_options' => [
                'label' => false,
            ],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__product__',
        ]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event): void {

                /** @var PackageOrdersDTO $PackageOrdersDTO */
                $packageOrdersDTO = $event->getData();
                $form = $event->getForm(); //->getParent();


                /**
                 * Все профили пользователя
                 */
                $profiles = $this->userProfileChoice
                    ->getActiveUserProfile($this->profileTokenStorage->getUser());


                /**
                 * Присваиваем идентификаторы заказа
                 * методы присвоят идентификаторы только в случае, если ранее они не были определены
                 */
                if(false === ($packageOrdersDTO->getProfile() instanceof UserProfileUid))
                {
                    $packageOrdersDTO->setProfile($this->profileTokenStorage->getProfile());
                }


                /* Склад назначения */
                $form->add(
                    'profile',
                    ChoiceType::class,
                    [
                        'choices' => $profiles,
                        'choice_value' => function(?UserProfileUid $profile) {
                            return $profile?->getValue();
                        },
                        'choice_label' => function(UserProfileUid $warehouse) {
                            return $warehouse->getAttr();
                        },

                        'label' => false,
                        'required' => false,
                    ]
                );

                if(false === $packageOrdersDTO->getOrders()->isEmpty())
                {
                    $products = $packageOrdersDTO->getProducts();

                    /**
                     * Данный флаг нужен, чтобы рассчитывать количество одного и того же продукта по всем заказам лишь при
                     * первоначальной загрузке формы
                     */
                    $firstRender = $products->isEmpty();

                    foreach($packageOrdersDTO->getOrders() as $orderDTO)
                    {
                        $order = $this->EntityManager->getRepository(Order::class)->find($orderDTO->getId());

                        if(false === ($order instanceof Order))
                        {
                            continue;
                        }

                        $orderEvent = $this->EntityManager
                            ->getRepository(OrderEvent::class)
                            ->find($order->getEvent());

                        if(false === ($orderEvent instanceof OrderEvent))
                        {
                            continue;
                        }


                        /** @var OrderProduct $product */
                        foreach($orderEvent->getProduct() as $product)
                        {
                            $productInDTO = false;

                            if(false === $products->isEmpty())
                            {
                                $productInDTO = $products->exists(
                                    function($key, PackageOrdersProductDTO $packageOrdersProductDTO) use ($product) {
                                        return $packageOrdersProductDTO->getProduct()->equals($product->getProduct())
                                            && (
                                                (is_null($packageOrdersProductDTO->getOffer()) === true && is_null($product->getOffer()) === true)
                                                || $packageOrdersProductDTO->getOffer()?->equals($product->getOffer())
                                            )
                                            && (
                                                (is_null($packageOrdersProductDTO->getVariation()) === true && is_null($product->getVariation()) === true)
                                                || $packageOrdersProductDTO->getVariation()?->equals($product->getVariation())
                                            )
                                            && (
                                                (is_null($packageOrdersProductDTO->getModification()) === true && is_null($product->getModification()) === true)
                                                || $packageOrdersProductDTO->getModification()?->equals($product->getModification())
                                            );
                                    }
                                );
                            }

                            $warehouse = $packageOrdersDTO->getProfile();

                            /** Получаем информацию о продукте */
                            $productUserBasketResult = $this->ProductUserBasketRepository
                                ->forEvent($product->getProduct())
                                ->forOffer($product->getOffer())
                                ->forVariation($product->getVariation())
                                ->forModification($product->getModification())
                                ->find();

                            if(false === ($productUserBasketResult instanceof ProductUserBasketResult))
                            {
                                return;
                            }

                            $totalStock = $this->WarehouseTotal->getProductProfileTotal(
                                $warehouse,
                                $productUserBasketResult->getProductId(),// $ProductUid,
                                $productUserBasketResult->getProductOfferConst(),//$ProductOfferConst,
                                $productUserBasketResult->getProductVariationConst(),//  $ProductVariationConst,
                                $productUserBasketResult->getProductModificationConst(), //$ProductModificationConst
                            );

                            $productUserBasketResult->setStock($totalStock);

                            if(true === $productInDTO)
                            {
                                $productDTO = $products->filter(
                                    function(PackageOrdersProductDTO $packageOrdersProductDTO) use ($product) {
                                        return $packageOrdersProductDTO->getProduct()->equals($product->getProduct())
                                            && (
                                                (is_null($packageOrdersProductDTO->getOffer()) === true && is_null($product->getOffer()) === true)
                                                || $packageOrdersProductDTO->getOffer()?->equals($product->getOffer())
                                            )
                                            && (
                                                (is_null($packageOrdersProductDTO->getVariation()) === true && is_null($product->getVariation()) === true)
                                                || $packageOrdersProductDTO->getVariation()?->equals($product->getVariation())
                                            )
                                            && (
                                                (is_null($packageOrdersProductDTO->getModification()) === true && is_null($product->getModification()) === true)
                                                || $packageOrdersProductDTO->getModification()?->equals($product->getModification())
                                            );
                                    }
                                )->current();

                                $productDTO->setCard($productUserBasketResult)->setStock($totalStock);

                                if(true === $firstRender)
                                {
                                    $productDTO->setTotal($productDTO->getTotal() + $product->getTotal());
                                }
                            }

                            if(false === $productInDTO)
                            {
                                $products->add(new PackageOrdersProductDTO()
                                    ->setProduct($product->getProduct())
                                    ->setOffer($product->getOffer())
                                    ->setVariation($product->getVariation())
                                    ->setModification($product->getModification())
                                    ->setTotal($product->getTotal())
                                    ->setStock($totalStock)
                                    ->setCard($productUserBasketResult)
                                );
                            }
                        }
                    }

                }
            },
        );


        /* Сохранить */
        $builder->add(
            'package',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']],
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PackageOrdersDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}