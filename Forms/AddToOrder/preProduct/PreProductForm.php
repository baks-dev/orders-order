<?php
/*
 * Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\Forms\AddToOrder\preProduct;

use BaksDev\Files\Resources\Twig\ImagePathExtension;
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
use BaksDev\Reference\Money\Twig\MoneyExtension;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use Symfony\Bundle\SecurityBundle\Security;
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

class PreProductForm extends AbstractType
{
    private false|int $discount = false;

    public function __construct(
        private readonly CategoryChoiceInterface $categoryChoice,
        private readonly ProductChoiceInterface $productChoice,
        private readonly ProductOfferChoiceInterface $productOfferChoice,
        private readonly ProductVariationChoiceInterface $productVariationChoice,
        private readonly ProductModificationChoiceInterface $productModificationChoice,
        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage,
        private readonly ImagePathExtension $ImagePathExtension,
        private readonly Security $security,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->discount = match (true)
        {
            $this->security->isGranted('ROLE_ADMIN') => 100,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_20') => 20,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_15') => 15,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_10') => 10,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_5') => 5,
            default => false,
        };

        $builder->add('category', ChoiceType::class, [
            'choices' => $this->categoryChoice->onlyActive()->findAll(),
            'choice_value' => function(?CategoryProductUid $category) {
                return $category?->getValue();
            },
            'choice_label' => function(CategoryProductUid $category) {
                return (is_int($category->getAttr()) ? str_repeat(' - ', $category->getAttr() - 1) : '').$category->getOptions();
            },
            'choice_attr' => function(?CategoryProductUid $category) {
                return $category ? [
                    'data-url' => empty($category->getParams()) ? null : $category->getParams()['category_url'],
                    'data-discount' => $this->discount,
                ] : [];
            },
            'label' => false,
            'required' => true,
        ]);


        /**
         * Продукция категории
         *
         * @var ProductEventUid $offer
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
                    }
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


        /**
         * Событие на изменение
         */

        $builder->get('preModification')->addEventListener(
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


        // Количество
        $builder->add('preTotal', IntegerType::class, ['required' => true]);
    }

    private function formProductModifier(FormInterface $form, ?CategoryProductUid $category): void
    {
        /** Получаем список доступной продукции (общие либо собственные) */
        $productChoice = $this->productChoice
            ->profile($this->UserProfileTokenStorage->getProfile())
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
                        'data-product-url' => $product->getParams() === null ? null : $product->getParams()[0]['product_url'],
                        'data-product-price' => $product->getParams() === null ? null : $product->getParams()[0]['product_price'],
                        'data-product-currency' => $product->getParams() === null ? null : $product->getParams()[0]['product_currency'],
                            'data-image-path' => $product->getParams() === null ? null : $this->ImagePathExtension->imagePath(
                            $product->getParams()[0]['product_image'],
                            $product->getParams()[0]['product_image_ext'],
                            $product->getParams()[0]['product_image_cdn'],
                        ),
                        'data-category-url' => $product->getParams() === null ? null : $product->getParams()[0]['category_url'],
                    ] : [];
                },
                'label' => false,
            ]
        );
    }

    private function formOfferModifier(FormInterface $form, ProductEventUid $product): void
    {
        /* Список торговых предложений продукции */
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
                            'data-product-price' => $offer->getParams() === null ? null : $offer->getParams()[0]['product_price'],
                            'data-product-currency' => $offer->getParams() === null ? null : $offer->getParams()[0]['product_currency'],
                            'data-product-article' => $offer->getParams() === null ? null : $offer->getParams()[0]['product_article'],
                            'data-offer-reference' => $offer->getCharacteristic() === null ? null : $offer->getCharacteristic(),
                            'data-offer-value' => $offer->getParams() === null ? null : $offer->getParams()[0]['product_offer_value'],
                            'data-offer-postfix' => $offer->getParams() === null ? null : $offer->getParams()[0]['product_offer_postfix'],
                            'data-image-path' => $offer->getParams() === null || $offer->getParams()[0]['product_image_ext'] === null ? null : $this->ImagePathExtension->imagePath(
                                $offer->getParams()[0]['product_image'],
                                $offer->getParams()[0]['product_image_ext'],
                                $offer->getParams()[0]['product_image_cdn'],
                            ),
                        ] : [];
                    },

                    'translation_domain' => $CurrentProductOfferUid->getCharacteristic(),
                    'label' => $CurrentProductOfferUid->getProperty(),
                    'placeholder' => sprintf('Выберите %s из списка...', $CurrentProductOfferUid->getProperty()),
                ]
            );
    }

    private function formVariationModifier(FormInterface $form, ProductOfferUid $offer): void
    {
        /* Список множественных вариантов торговых предложений */
        $variations = $this->productVariationChoice->fetchProductVariationExistsByOffer($offer);

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
                            'data-product-price' => $variation->getParams() === null ? null : $variation->getParams()[0]['product_price'],
                            'data-product-currency' => $variation->getParams() === null ? null : $variation->getParams()[0]['product_currency'],
                            'data-product-article' => $variation->getParams() === null ? null : $variation->getParams()[0]['product_article'],
                            'data-variation-reference' => $variation->getCharacteristic() === null ? null : $variation->getCharacteristic(),
                            'data-variation-value' => $variation->getParams() === null ? null : $variation->getParams()[0]['product_variation_value'],
                            'data-variation-postfix' => $variation->getParams() === null ? null : $variation->getParams()[0]['product_variation_postfix'],
                            'data-image-path' => $variation->getParams() === null || $variation->getParams()[0]['product_image_ext'] === null ? null : $this->ImagePathExtension->imagePath(
                                $variation->getParams()[0]['product_image'],
                                $variation->getParams()[0]['product_image_ext'],
                                $variation->getParams()[0]['product_image_cdn'],
                            )
                        ] : [];
                    },
                    'translation_domain' => $CurrentProductVariationUid->getCharacteristic(),
                    'label' => $CurrentProductVariationUid->getProperty(),
                    'placeholder' => sprintf(
                        'Выберите %s из списка...',
                        $CurrentProductVariationUid->getProperty()
                    ),
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
                            'data-product-image' => $modification->getParams() === null ? null : $modification->getParams()[0]['product_image'],
                            'data-product-price' => $modification->getParams() === null ? null : $modification->getParams()[0]['product_price'],
                            'data-product-currency' => $modification->getParams() === null ? null : $modification->getParams()[0]['product_currency'],
                            'data-product-article' => $modification->getParams() === null ? null : $modification->getParams()[0]['product_article'],
                            'data-modification-reference' => $modification->getCharacteristic() === null ? null : $modification->getCharacteristic(),
                            'data-modification-value' => $modification->getParams() === null ? null : $modification->getParams()[0]['product_modification_value'],
                            'data-modification-postfix' => $modification->getParams() === null ? null : $modification->getParams()[0]['product_modification_postfix'],
                            'data-image-path' => $modification->getParams() === null || $modification->getParams()[0]['product_image_ext'] === null ? null : $this->ImagePathExtension->imagePath(
                                $modification->getParams()[0]['product_image'],
                                $modification->getParams()[0]['product_image_ext'],
                                $modification->getParams()[0]['product_image_cdn'],
                            ),
                        ] : [];
                    },
                    'translation_domain' => $CurrentProductModificationUid->getCharacteristic(),
                    'label' => $CurrentProductModificationUid->getProperty(),
                    'placeholder' => sprintf(
                        'Выберите %s из списка...',
                        $CurrentProductModificationUid->getProperty()
                    ),
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