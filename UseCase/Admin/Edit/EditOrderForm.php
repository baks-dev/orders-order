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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit;

use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductForm;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Service\OrderServiceForm;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\OrderUserForm;
use BaksDev\Services\BaksDevServicesBundle;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EditOrderForm extends AbstractType
{

    private false|int $discount = false;

    public function __construct(
        private readonly Security $security,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* Коллекция продукции */
        $builder->add('product', CollectionType::class, [
            'entry_type' => OrderProductForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__product__',
        ]);

        /**
         * Коллекция услуг в заказе (ЕСЛИ УСТАНОВЛЕН МОДУЛЬ services)
         */

        if(true === class_exists(BaksDevServicesBundle::class))
        {
            $builder->add('serv', CollectionType::class, [
                'entry_type' => OrderServiceForm::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'by_reference' => false,
                'allow_delete' => true,
                'allow_add' => true,
                'prototype_name' => '__service__',
            ]);

        }

        $builder->add('usr', OrderUserForm::class, ['label' => false]);

        $builder->add('comment', TextareaType::class, ['required' => false]);

        /* Процент скидки для заказа */
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
            'attr' => ['min' => $this->discount ? $this->discount * -1 : 0],
            'required' => false,
        ]);

        $builder->add(
            'order',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );

        // @TODO теперь можем сохранять в любом статусе
        //
        //        $builder->addEventListener(
        //            FormEvents::PRE_SET_DATA,
        //            function(FormEvent $event): void {
        //
        //                $form = $event->getForm();
        //
        //                /** @var EditOrderDTO $data */
        //                $data = $event->getData();
        //
        //                /** Если заказ выполнен - не отображаем кнопку для сохранения изменений */
        //                if(false === $data->getStatus()->equals(OrderStatusCompleted::class))
        //                {
        //                    $form->add(
        //                        'order',
        //                        SubmitType::class,
        //                        ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        //                    );
        //                }
        //            }
        //        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => EditOrderDTO::class,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
            ]
        );
    }
}