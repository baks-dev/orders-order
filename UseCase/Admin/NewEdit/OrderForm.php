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

namespace BaksDev\Orders\Order\UseCase\Admin\NewEdit;

use BaksDev\Products\Category\Repository\CategoryChoice\CategoryChoiceInterface;
use BaksDev\Products\Category\Type\Id\CategoryUid;
use BaksDev\Products\Product\Repository\ProductOfferChoice\ProductOfferChoiceInterface;
use BaksDev\Products\Product\Repository\ProductsChoice\ProductsChoiceInterface;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderForm extends AbstractType
{
    
    private ProductsChoiceInterface $products;
    private CategoryChoiceInterface $category;
    private ProductOfferChoiceInterface $offers;
    
    public function __construct(
      ProductsChoiceInterface $products,
      CategoryChoiceInterface $category,
      ProductOfferChoiceInterface $offers
    )
    {
    
        $this->products = $products;
        $this->category = $category;
        $this->offers = $offers;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
    
    
        $builder
          ->add('category', ChoiceType::class, [
            'choices' => $this->category->get(), // $this->category->get(),
            'choice_value' => function (?CategoryUid $category)
            {
                return $category?->getValue();
            },
            'choice_label' => function (?CategoryUid $category)
            {
                return $category?->getOption();
            },
        
            'label' => false,
            'expanded' => false,
            'multiple' => false,
            'required' => true,
          ]);
    
    
        $builder
          ->add('product', ChoiceType::class, [
            'choices' => $this->products->get(), // $this->category->get(),
            'choice_value' => function (? ProductUid $product)
            {
                return $product?->getValue();
            },
            'choice_label' => function (?ProductUid $product)
            {
                return $product?->getName();
            },
        
            'label' => false,
            'expanded' => false,
            'multiple' => false,
            'required' => true,
          ]);
        
        
        $builder
          ->add('offer', ChoiceType::class, [
            'choices' => $this->offers->get(), // $this->category->get(),

            'group_by' => function($offer) {
              
                return $offer->getOffers() ? 'Группа '.$offer->getOffers() : '';

            },
            'choice_value' => function ( $offer)
            {
                return $offer?->getValue();
            },
            'choice_label' => function ( $offer)
            {
                return $offer?->getName();
            },
        
            'label' => false,
            'expanded' => false,
            'multiple' => false,
            'required' => true,
          ]);
        
        

        $builder->add('price', Price\PriceForm::class, ['label' => false]);
        
        /* Сохранить ******************************************************/
        $builder->add
        (
          'Save',
          SubmitType::class,
          ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]);

    }
    
    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults
        (
          [
            'data_class' => OrderDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
          ]);
    }
    
}
