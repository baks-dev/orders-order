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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\Products\Items\DeletedItem;

use BaksDev\Orders\Order\Type\Items\Const\OrderProductItemConst;
use BaksDev\Orders\Order\Type\Items\OrderProductItemUid;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DeletedItemForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('id', HiddenType::class);

        $builder->get('id')->addModelTransformer(
            new CallbackTransformer(
                function(?OrderProductItemUid $id) {
                    return $id instanceof OrderProductItemUid ? $id->getValue() : $id;
                },
                function(string $id) {
                    return new OrderProductItemUid($id);
                }
            )
        );

        $builder->add('product', HiddenType::class);

        $builder->get('product')->addModelTransformer(
            new CallbackTransformer(
                function(?OrderProductUid $product) {
                    return $product instanceof OrderProductUid ? $product->getValue() : $product;
                },
                function(string $product) {
                    return new OrderProductUid($product);
                }
            )
        );

        $builder->add('const', HiddenType::class);

        $builder->get('const')->addModelTransformer(
            new CallbackTransformer(
                function(?OrderProductItemConst $const) {
                    return $const instanceof OrderProductItemConst ? $const->getValue() : $const;
                },
                function(string $const) {
                    return new OrderProductItemConst($const);
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeletedItemDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}