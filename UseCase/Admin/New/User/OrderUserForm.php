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

namespace BaksDev\Orders\Order\UseCase\Admin\New\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

final class OrderUserForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add(
            'userProfile',
            UserProfile\UserProfileForm::class,
            [
                'label' => false,
                'constraints' => [new Valid()],
            ]
        );

        $builder->add('payment', Payment\OrderPaymentForm::class, ['label' => false,]);

        $builder->add('delivery', Delivery\OrderDeliveryForm::class, ['label' => false,]);


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($options) {

                /** @var OrderUserDTO $data */
                $data = $event->getData();
                $form = $event->getForm();


                //                dump('PRE_SET_DATA '.$this::class);
                //                dump($data->getUserProfile()?->getType());


                if($data->getUserProfile()?->getType())
                {
                    $form->add(
                        'delivery',
                        Delivery\OrderDeliveryForm::class,
                        [
                            'label' => false,
                            'user_profile_type' => $data->getUserProfile()?->getType(),
                        ]
                    );

                    $form->add(
                        'payment',
                        Payment\OrderPaymentForm::class,
                        [
                            'label' => false,
                            'user_profile_type' => $data->getUserProfile()?->getType(),
                        ]
                    );
                }
            }
        );


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {

                //$data = $event->getData();

                $form = $event->getForm()->getParent();

                $form->add('usr', self::class, ['label' => false]);

            }
        );
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderUserDTO::class,
            'data_user_profile_type' => null
        ]);
    }

}
