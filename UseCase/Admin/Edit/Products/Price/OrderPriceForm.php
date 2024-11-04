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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\Products\Price;

use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderPriceForm extends AbstractType
{
    private false|int $discount = false;

    public function __construct(private readonly Security $security) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->discount = match (true)
        {
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_20') => 20,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_15') => 15,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_10') => 10,
            $this->security->isGranted('ROLE_ORDERS_DISCOUNT_5') => 5,
            default => false,
        };

        $builder->add('total', TextType::class);

        if($this->discount)
        {
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function(FormEvent $event) use ($builder): void {

                    /** @var OrderPriceDTO $OrderPriceDTO */
                    $OrderPriceDTO = $event->getData();

                    if($OrderPriceDTO)
                    {
                        $form = $event->getForm();

                        /**  @var OrderProductDTO $OrderProductDTO */
                        $OrderProductDTO = $form->getParent()?->getData();
                        $card = $OrderProductDTO->getCard();

                        if(!isset($card['product_price']))
                        {
                            return;
                        }

                        $productPrice = new Money($card['product_price'], true);
                        $percent = $productPrice->percent($this->discount);
                        $min = $productPrice->sub($percent);


                        $form->add(
                            $builder
                                ->create(
                                    'price',
                                    MoneyType::class,
                                    [
                                        'attr' => ['min' => $min],
                                        'required' => true,
                                        'currency' => false,
                                        'auto_initialize' => false,
                                        //'constraints' => [new Range(min: $min->getValue())]
                                    ]
                                )
                                ->addModelTransformer(
                                    new CallbackTransformer(
                                        function($price) {
                                            return $price instanceof Money ? $price->getValue() : $price;
                                        },
                                        function($price) {
                                            return new Money($price);
                                        }
                                    )
                                )
                                ->getForm()
                        );
                    }
                }
            );
        }


    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderPriceDTO::class,
        ]);
    }

}
