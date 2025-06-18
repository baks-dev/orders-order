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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Delivery\Type\Field\DeliveryFieldUid;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Repository\DeliveryByProfileChoice\DeliveryByProfileChoiceInterface;
use BaksDev\Orders\Order\Repository\FieldByDeliveryChoice\FieldByDeliveryChoiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery\Price\OrderDeliveryPriceForm;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery\Field\OrderDeliveryFieldForm;
use BaksDev\Orders\Order\UseCase\Admin\Edit\User\Delivery\Field\OrderDeliveryFieldDTO;

final class OrderDeliveryForm extends AbstractType
{
    public function __construct(
        private readonly DeliveryByProfileChoiceInterface $deliveryChoice,
        private readonly FieldByDeliveryChoiceInterface $deliveryFields
    ) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('delivery', HiddenType::class);

        $builder->get('delivery')->addModelTransformer(
            new CallbackTransformer(
                function($delivery) {
                    return $delivery instanceof DeliveryUid ? $delivery->getValue() : $delivery;
                },
                function($delivery) {
                    return new DeliveryUid($delivery);
                },
            ),
        );


        $builder->add('latitude', HiddenType::class, ['required' => false, 'attr' => ['data-latitude' => 'true']]);

        $builder->get('latitude')->addModelTransformer(
            new CallbackTransformer(
                function($gps) {
                    return $gps instanceof GpsLatitude ? $gps->getValue() : $gps;
                },
                function($gps) {
                    return new GpsLatitude($gps);
                },
            ),
        );

        /* GPS долгота:*/

        $builder->add('longitude', HiddenType::class, ['required' => false, 'attr' => ['data-longitude' => 'true']]);

        $builder->get('longitude')->addModelTransformer(
            new CallbackTransformer(
                function($gps) {
                    return $gps instanceof GpsLongitude ? $gps->getValue() : $gps;
                },
                function($gps) {
                    return new GpsLongitude($gps);
                },
            ),
        );

        /**
         * Дата доставки заказа
         */
        $builder->add('deliveryDate', DateType::class, [
            'widget' => 'single_text',
            'html5' => false,
            'required' => false,
            'format' => 'dd.MM.yyyy',
            'input' => 'datetime_immutable',
        ]);

        $builder->add(
            'price',
            OrderDeliveryPriceForm::class,
            ['label' => false],
        );


        /* Коллекция пользовательских свойств */
        $builder->add('field', CollectionType::class, [
            'entry_type' => OrderDeliveryFieldForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__delivery_field__',
        ]);


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($options) {


                $data = $event->getData();
                $form = $event->getForm();

                /**
                 * Если в параметре $options['user_profile_type'] передан NULL
                 * в массиве должен появится публичный способ доставки, например Самовывоз
                 */
                $deliveryChoice = $this->deliveryChoice->fetchDeliveryByProfile($options['user_profile_type']);

                /** @var DeliveryUid $currentDelivery */
                $currentDelivery = current($deliveryChoice);

                if(false === ($currentDelivery instanceof DeliveryUid))
                {
                    return;
                }

                $deliveryHelp = $currentDelivery->getOption();
                $deliveryChecked = $currentDelivery;


                /** @var DeliveryUid $Delivery */
                $Delivery = $data->getDelivery();


                if($Delivery)
                {
                    $deliveryCheckedFilter = array_filter($deliveryChoice, function($v, $k) use ($Delivery) {
                        return $v->equals($Delivery);
                    }, ARRAY_FILTER_USE_BOTH);


                    if($deliveryCheckedFilter)
                    {
                        /** @var DeliveryUid $deliveryChecked */
                        $deliveryChecked = current($deliveryCheckedFilter);

                        /* Присваиваем способу доставки - событие  (для расчета стоимости)  */
                        $data->setEvent($deliveryChecked->getEvent());
                        $deliveryHelp = $deliveryChecked?->getOption();
                    }
                }


                $form
                    ->add('delivery', ChoiceType::class, [
                        'choices' => $deliveryChoice,
                        'choice_value' => function(?DeliveryUid $delivery) {
                            return $delivery?->getValue();
                        },

                        'choice_label' => function(DeliveryUid $delivery) {
                            return $delivery->getAttr();
                        },

                        'choice_attr' => function(DeliveryUid $choice) use ($deliveryChecked) {
                            return [
                                'checked' => ($choice->equals($deliveryChecked)),
                                'data-price' => $choice->getPrice()?->getValue(),
                                'data-excess' => $choice->getExcess()?->getValue(),
                                'data-currency' => $choice->getCurrency(),
                            ];
                        },

                        'attr' => ['class' => 'd-flex gap-3'],
                        'help' => $deliveryHelp,
                        'label' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'required' => true,
                    ]);


                // Condition is always 'true' because '$deliveryChecked' is evaluated at this point
                $fields = $this->deliveryFields->fetchDeliveryFields($deliveryChecked);


                /** Получаем пользовательские поля */
                if($fields)
                {
                    /** @var ArrayCollection $dataFields */
                    $dataFields = $data->getField();

                    $data->setField(new ArrayCollection());


                    $setField = new ArrayCollection();

                    /** @var DeliveryFieldUid $field */
                    foreach($fields as $field)
                    {
                        $OrderDeliveryFieldDTO = new OrderDeliveryFieldDTO();
                        $OrderDeliveryFieldDTO->setField($field);


                        /**
                         * Находим заполненное значение в коллекции и присваиваем
                         *
                         * @var OrderDeliveryFieldDTO $element
                         */
                        $dataFieldFilter = $dataFields->filter(function(OrderDeliveryFieldDTO $element) use (
                            $field
                        ) {
                            return $field->equals($element->getField());
                        });

                        if(!$dataFieldFilter->isEmpty() && $dataFieldFilter->current()->getValue())
                        {
                            $OrderDeliveryFieldDTO->setValue($dataFieldFilter->current()->getValue());
                        }

                        $setField->add($OrderDeliveryFieldDTO);
                    }

                    $data->setField($setField);


                    /* Коллекция продукции */
                    $form->add('field', CollectionType::class, [
                        'entry_type' => OrderDeliveryFieldForm::class,
                        'entry_options' => ['label' => false],
                        'label' => false,
                        'by_reference' => false,
                        'allow_delete' => true,
                        'allow_add' => true,
                        'prototype_name' => '__delivery_field__',
                    ]);

                }


            },
        );

    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderDeliveryDTO::class,
            'user_profile_type' => null,
        ]);
    }

}
