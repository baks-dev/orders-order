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

namespace BaksDev\Orders\Order\UseCase\Admin\New\preProduct;


use BaksDev\Products\Product\Repository\ProductChoice\ProductChoiceInterface;
use BaksDev\Products\Product\Repository\ProductModificationChoice\ProductModificationChoiceInterface;
use BaksDev\Products\Product\Repository\ProductOfferChoice\ProductOfferChoiceInterface;
use BaksDev\Products\Product\Repository\ProductVariationChoice\ProductVariationChoiceInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PreProductForm extends AbstractType
{

//    private ProductChoiceWarehouseInterface $productChoiceWarehouse;
//    private ProductVariationChoiceWarehouseInterface $productVariationChoiceWarehouse;
//    private ProductOfferChoiceWarehouseInterface $productOfferChoiceWarehouse;
//    private ProductModificationChoiceWarehouseInterface $productModificationChoiceWarehouse;
//    private ProductWarehouseChoiceInterface $productWarehouseChoice;
//    private UserProfileChoiceInterface $userProfileChoice;
//    private TokenStorageInterface $tokenStorage;
//    private UserUid $user;

    private ProductChoiceInterface $productChoice;
    private ProductOfferChoiceInterface $productOfferChoice;
    private ProductVariationChoiceInterface $productVariationChoice;
    private ProductModificationChoiceInterface $productModificationChoice;

    public function __construct(
//        UserProfileChoiceInterface $userProfileChoice,
//        ProductChoiceWarehouseInterface $productChoiceWarehouse,
//        ProductOfferChoiceWarehouseInterface $productOfferChoiceWarehouse,
//        ProductVariationChoiceWarehouseInterface $productVariationChoiceWarehouse,
//        ProductModificationChoiceWarehouseInterface $productModificationChoiceWarehouse,
//        ProductWarehouseChoiceInterface $productWarehouseChoice,
//        TokenStorageInterface $tokenStorage,

        ProductChoiceInterface $productChoice,
        ProductOfferChoiceInterface $productOfferChoice,
        ProductVariationChoiceInterface $productVariationChoice,
        ProductModificationChoiceInterface $productModificationChoice


    )
    {

//        $this->productChoiceWarehouse = $productChoiceWarehouse;
//        $this->productOfferChoiceWarehouse = $productOfferChoiceWarehouse;
//        $this->productVariationChoiceWarehouse = $productVariationChoiceWarehouse;
//        $this->productModificationChoiceWarehouse = $productModificationChoiceWarehouse;
//        $this->productWarehouseChoice = $productWarehouseChoice;
//        $this->userProfileChoice = $userProfileChoice;
//        $this->tokenStorage = $tokenStorage;


        $this->productChoice = $productChoice;
        $this->productOfferChoice = $productOfferChoice;
        $this->productVariationChoice = $productVariationChoice;
        $this->productModificationChoice = $productModificationChoice;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * Продукция (событие)
         *
         * @var ProductEventUid $product
         */
        //$builder->add('preProduct', TextType::class, ['attr' => ['disabled' => true]]);


        /** Получаем список доступной продукции */
        $productChoice =  $this->productChoice->fetchAllProductEventByExists();
        $productChoice = iterator_to_array($productChoice);

            $builder->add(
                'preProduct',
                ChoiceType::class,
                [
                    'choices' => $productChoice,
                    'choice_value' => function(?ProductEventUid $product) {
                        return $product?->getValue();
                    },

                    'choice_label' => function(ProductEventUid $product) {
                        return $product->getAttr().' ('.$product->getOption().')';
                    },
                    'choice_attr' => function(?ProductEventUid $product) {
                        return $product ? [
                            'data-max' => $product->getOption(),
                            'data-name' => $product->getAttr(),
                        ] : [];
                    },
                    'label' => false,
                ]
            );
        //}


        /**
         * Торговые предложения
         *
         * @var ProductOfferUid $offer
         */

        $builder->add(
            'preOffer',
            HiddenType::class,
        );

        $builder->get('preOffer')->addModelTransformer(
            new CallbackTransformer(
                function($offer) {
                    return $offer instanceof ProductOfferUid ? $offer->getValue() : $offer;
                },
                function($offer) {
                    return $offer ? new ProductOfferUid($offer) : null;
                }
            ),
        );

        /**
         * Множественный вариант торгового предложения
         *
         * @var ProductVariationUid $variation
         */

        $builder->add(
            'preVariation',
            HiddenType::class,
        );

        $builder->get('preVariation')->addModelTransformer(
            new CallbackTransformer(
                function($variation) {
                    return $variation instanceof ProductVariationUid ? $variation->getValue() : $variation;
                },
                function($variation) {
                    return $variation ? new ProductVariationUid($variation) : null;
                }
            ),
        );

        /**
         * Модификация множественного варианта торгового предложения
         *
         * @var ProductModificationUid $modification
         */

        $builder->add(
            'preModification',
            HiddenType::class,
        );

        $builder->get('preModification')->addModelTransformer(
            new CallbackTransformer(
                function($modification) {
                    return $modification instanceof ProductModificationUid ? $modification->getValue() : $modification;
                },
                function($modification) {
                    return $modification ? new ProductModificationUid($modification) : null;
                }
            ),
        );


        $builder->get('preModification')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void
            {
                $parent = $event->getForm()->getParent();

                if(!$parent)
                {
                    return;
                }

                $product = $parent->get('preProduct')->getData();
                $offer = $parent->get('preOffer')->getData();
                $variation = $parent->get('preVariation')->getData();
                //$modification = $parent->get('preModification')->getData();

                if($product)
                {
                    $this->formOfferModifier($event->getForm()->getParent(), $product);
                }

                if($product && $offer)
                {
                    $this->formVariationModifier($event->getForm()->getParent(), $product, $offer);
                }

                if($product && $offer && $variation)
                {
                    $this->formModificationModifier($event->getForm()->getParent(), $product, $offer, $variation);
                }

            },
        );


        // Количество
        $builder->add('preTotal', IntegerType::class, ['required' => false]);


    }

    private function formOfferModifier(FormInterface $form, ProductEventUid $product): void
    {

        /* Список торговых предложений продукции */

        $offer = $this->productOfferChoice->fetchProductOfferExistsByProductEvent($product);

        // Если у продукта нет ТП
        if(!$offer?->valid())
        {
            $form->add(
                'preOffer',
                HiddenType::class,
            );

            return;
        }


        /** @var ProductOfferUid $CurrentProductOfferUid */
        $CurrentProductOfferUid = $offer->current();

        $form
            ->add(
                'preOffer',
                ChoiceType::class,
                [
                    'choices' => $offer,
                    'choice_value' => function(?ProductOfferUid $offer) {
                        return $offer?->getValue();
                    },
                    'choice_label' => function(ProductOfferUid $offer) {
                        return $offer->getAttr().' ('.$offer->getOption().')';
                    },
                    'choice_attr' => function(?ProductOfferUid $offer) {
                        return $offer ? [
                            'data-max' => $offer->getOption(),
                            'data-name' => $offer->getAttr()
                        ] : [];
                    },
                    'label' => $CurrentProductOfferUid->getProperty(),
                    'placeholder' => sprintf('Выберите %s из списка...', $CurrentProductOfferUid->getProperty()),
                ]
            );
    }

    private function formVariationModifier(
        FormInterface $form,
        ProductEventUid $product,
        ProductOfferUid $offer
    ): void
    {

        $variations = $this->productVariationChoice->fetchProductVariationExistsByOffer($offer);

        // Если у продукта нет ТП
        if(!$variations?->valid())
        {
            $form->add(
                'preVariation',
                HiddenType::class,
            );

            return;
        }

        /** @var ProductVariationUid $CurrentProductVariationUid */
        $CurrentProductVariationUid = $variations->current();

        $form
            ->add(
                'preVariation',
                ChoiceType::class,
                [
                    'choices' => $variations,
                    'choice_value' => function(?ProductVariationUid $variation) {
                        return $variation?->getValue();
                    },
                    'choice_label' => function(ProductVariationUid $variation) {
                        return $variation->getAttr().' ('.$variation->getOption().')';
                    },
                    'choice_attr' => function(?ProductVariationUid $variation) {
                        return $variation ? [
                            'data-max' => $variation->getOption(),
                            'data-name' => $variation->getAttr()
                        ] : [];
                    },
                    'label' => $CurrentProductVariationUid->getProperty(),
                    'placeholder' => sprintf('Выберите %s из списка...', $CurrentProductVariationUid->getProperty()),
                ],
            );
    }

    private function formModificationModifier(
        FormInterface $form,
        ProductEventUid $product,
        ProductOfferUid $offer,
        ProductVariationUid $variation,
    ): void
    {
        $modifications = $this->productModificationChoice->fetchProductModificationExistsByVariation($variation);

        // Если у продукта нет ТП
        if(!$modifications?->valid())
        {
            $form->add(
                'preModification',
                HiddenType::class,
            );

            return;
        }

        /** @var ProductModificationUid $CurrentProductModificationUid */
        $CurrentProductModificationUid = $modifications->current();

        $form
            ->add(
                'preModification',
                ChoiceType::class,
                [
                    'choices' => $modifications,
                    'choice_value' => function(?ProductModificationUid $modification) {
                        return $modification?->getValue();
                    },
                    'choice_label' => function(ProductModificationUid $modification) {
                        return $modification->getAttr().' ('.$modification->getOption().')';
                    },
                    'choice_attr' => function(?ProductModificationUid $modification) {
                        return $modification ? [
                            'data-max' => $modification->getOption(),
                            'data-name' => $modification->getAttr()
                        ] : [];
                    },
                    'label' => $CurrentProductModificationUid->getProperty(),
                    'placeholder' => sprintf('Выберите %s из списка...', $CurrentProductModificationUid->getProperty()),
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PreProductDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}