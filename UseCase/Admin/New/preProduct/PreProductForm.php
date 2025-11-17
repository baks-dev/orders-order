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

namespace BaksDev\Orders\Order\UseCase\Admin\New\preProduct;

use BaksDev\Products\Category\Repository\CategoryChoice\CategoryChoiceInterface;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Repository\ProductChoice\ProductChoiceInterface;
use BaksDev\Products\Product\Repository\ProductModificationChoice\ProductModificationChoiceInterface;
use BaksDev\Products\Product\Repository\ProductOfferChoice\ProductOfferChoiceInterface;
use BaksDev\Products\Product\Repository\ProductVariationChoice\ProductVariationChoiceInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PreProductForm extends AbstractType
{
    private false|int $discount = false;

    public function __construct(
        private readonly Security $security,
        private readonly CategoryChoiceInterface $categoryChoice,
        private readonly ProductChoiceInterface $productChoice,
        private readonly ProductOfferChoiceInterface $productOfferChoice,
        private readonly ProductVariationChoiceInterface $productVariationChoice,
        private readonly ProductModificationChoiceInterface $productModificationChoice,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('category', ChoiceType::class, [
            'choices' => $this->categoryChoice->findAll(),
            'choice_value' => function(?CategoryProductUid $category) {
                return $category?->getValue();
            },
            'choice_label' => function(CategoryProductUid $category) {
                return (is_int($category->getAttr()) ? str_repeat(' - ', $category->getAttr() - 1) : '').$category->getOptions();
            },
            'label' => false,
            'required' => false,
        ]);


        /**
         * Продукция категории
         *
         * @var ProductOfferUid $offer
         */


        $builder->add(
            'preProduct',
            HiddenType::class,
        );

        $builder
            ->get('preProduct')->addModelTransformer(
                new CallbackTransformer(
                    function($product) {
                        return $product instanceof ProductEventUid ? $product->getValue() : $product;
                    },
                    function($product) {
                        return $product ? new ProductEventUid($product) : null;
                    },
                ),
            );


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
                },
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
                },
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
                },
            ),
        );


        /**
         * Событие на изменение
         */

        $builder->get('preVariation')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {

                $parent = $event->getForm()->getParent();

                if(!$parent)
                {
                    return;
                }

                $category = $parent->get('category')->getData();
                $product = $parent->get('preProduct')->getData();
                $offer = $parent->get('preOffer')->getData();
                $variation = $parent->get('preVariation')->getData();

                if($category)
                {
                    $this->formProductModifier($event->getForm()->getParent(), $category);
                }

                if($product)
                {
                    $this->formOfferModifier($event->getForm()->getParent(), $product);
                }

                if($offer)
                {
                    $this->formVariationModifier($event->getForm()->getParent(), $offer);
                }

                if($variation)
                {
                    $this->formModificationModifier($event->getForm()->getParent(), $variation);
                }
            },
        );

        /** Количество */
        $builder->add('preTotal', IntegerType::class, ['required' => false]);

        /** Персональная скидка */
        $this->discount = match (true)
        {
            $this->security->isGranted('ROLE_ADMIN') => 100,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_20') => 20,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_15') => 15,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_10') => 10,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_5') => 5,
            default => false,
        };

        $builder->add('discount', IntegerType::class, [
            'disabled' => $this->discount === false,
            'required' => false
        ]);
    }

    private function formProductModifier(FormInterface $form, ?CategoryProductUid $category): void
    {
        /** Получаем список доступной продукции (общие либо собственные) */
        $productChoice = $this->productChoice
            ->fetchAllProductEventByExists($category ?: false);

        $form->add(
            'preProduct',
            ChoiceType::class,
            [
                'choices' => $productChoice,
                'choice_value' => function(?ProductEventUid $product) {
                    return $product?->getValue();
                },

                'choice_label' => function(ProductEventUid $product) {
                    return $product->getAttr();
                },
                'choice_attr' => function(?ProductEventUid $product) {
                    return $product ? [
                        'data-filter' => ' ['.$product->getOption().']',
                        'data-max' => $product->getOption(),
                        'data-name' => $product->getAttr(),
                    ] : [];
                },
                'label' => false,
            ],
        );
    }

    private function formOfferModifier(FormInterface $form, ProductEventUid $product): void
    {
        /** Список торговых предложений продукции */
        $offer = $this->productOfferChoice
            ->findOnlyExistsByProductEvent($product);

        // Если у продукта нет ТП
        if(false === $offer->valid())
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
                        return trim($offer->getAttr());
                    },
                    'choice_attr' => function(?ProductOfferUid $offer) {
                        return $offer ? [
                            'data-filter' => ' ['.$offer->getOption().']',
                            'data-max' => $offer->getOption(),
                            'data-name' => $offer->getAttr(),
                        ] : [];
                    },

                    'translation_domain' => $CurrentProductOfferUid->getCharacteristic(),
                    'label' => $CurrentProductOfferUid->getProperty(),
                    'placeholder' => sprintf('Выберите %s из списка...', $CurrentProductOfferUid->getProperty()),
                ],
            );
    }

    private function formVariationModifier(FormInterface $form, ProductOfferUid $offer): void
    {
        /* Список множественных вариантов торговых предложений */
        $variations = $this->productVariationChoice
            ->fetchProductVariationExistsByOffer($offer);

        // Если у продукта нет ТП
        if(false === $variations->valid())
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
                        return trim($variation->getAttr());
                    },
                    'choice_attr' => function(?ProductVariationUid $variation) {
                        return $variation ? [
                            'data-filter' => ' ['.$variation->getOption().']',
                            'data-max' => $variation->getOption(),
                            'data-name' => $variation->getAttr(),
                        ] : [];
                    },
                    'translation_domain' => $CurrentProductVariationUid->getCharacteristic(),
                    'label' => $CurrentProductVariationUid->getProperty(),
                    'placeholder' => sprintf('Выберите %s из списка...', $CurrentProductVariationUid->getProperty()),
                ],
            );
    }

    private function formModificationModifier(FormInterface $form, ProductVariationUid $variation): void
    {
        /* Список модификаций множественного варианта торгового предложения */
        $modifications = $this->productModificationChoice->fetchProductModificationExistsByVariation($variation);

        // Если у продукта нет ТП
        if(false === $modifications->valid())
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
                        return trim($modification->getAttr());
                    },
                    'choice_attr' => function(?ProductModificationUid $modification) {
                        return $modification ? [
                            'data-filter' => ' ['.$modification->getOption().']',
                            'data-max' => $modification->getOption(),
                            'data-name' => $modification->getAttr(),
                        ] : [];
                    },
                    'translation_domain' => $CurrentProductModificationUid->getCharacteristic(),
                    'label' => $CurrentProductModificationUid->getProperty(),
                    'placeholder' => sprintf('Выберите %s из списка...', $CurrentProductModificationUid->getProperty()),
                ],
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
