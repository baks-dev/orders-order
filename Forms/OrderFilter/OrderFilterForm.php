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

namespace BaksDev\Orders\Order\Forms\OrderFilter;

use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderFilterForm extends AbstractType
{
    public function __construct(private readonly RequestStack $request) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('date', DateType::class, [
            'widget' => 'single_text',
            'html5' => false,
            'attr' => ['class' => 'js-datepicker'],
            'required' => false,
            'format' => 'dd.MM.yyyy',
            'input' => 'datetime_immutable',
        ]);


        /**
         * Категория производства
         */

        $builder
            ->add('status', ChoiceType::class, [
                'choices' => OrderStatus::cases(),
                'choice_value' => function(?OrderStatus $status) {
                    return $status?->getOrderStatusValue();
                },
                'choice_label' => function(OrderStatus $status) {

                    return $status->getOrderStatusValue();
                },

                'translation_domain' => 'status.order',
                'label' => false,
                'expanded' => false,
                'multiple' => false,
                'required' => false,
            ]);


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {
                /** @var OrderFilterDTO $data */
                $data = $event->getData();

                $this->request->getSession()->set(OrderFilterDTO::date, $data->getDate());
                $this->request->getSession()->set(OrderFilterDTO::status, $data->getStatus());
            }
        );


        $builder->add(
            'back',
            SubmitType::class,
            ['label' => 'Back', 'label_html' => true, 'attr' => ['class' => 'btn-light']]
        );


        $builder->add(
            'next',
            SubmitType::class,
            ['label' => 'next', 'label_html' => true, 'attr' => ['class' => 'btn-light']]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => OrderFilterDTO::class,
                'method' => 'POST',
            ]
        );
    }
}
