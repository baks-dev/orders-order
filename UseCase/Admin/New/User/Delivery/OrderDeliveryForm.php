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

declare(strict_types=1);

namespace BaksDev\Orders\Order\UseCase\Admin\New\User\Delivery;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Delivery\Repository\DeliveryByTypeProfileChoice\DeliveryByTypeProfileChoiceInterface;
use BaksDev\Delivery\Repository\FieldByDeliveryChoice\FieldByDeliveryChoiceInterface;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Users\Profile\TypeProfile\Repository\TypeProfileChoice\TypeProfileChoiceRepository;
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

final class OrderDeliveryForm extends AbstractType
{
    private DeliveryByTypeProfileChoiceInterface $deliveryChoice;

    private FieldByDeliveryChoiceInterface $deliveryFields;

    private TypeProfileChoiceRepository $profileChoice;


    public function __construct(
        DeliveryByTypeProfileChoiceInterface $deliveryChoice,
        FieldByDeliveryChoiceInterface $deliveryFields,
        TypeProfileChoiceRepository $profileChoice
    )
    {
        $this->deliveryChoice = $deliveryChoice;
        $this->deliveryFields = $deliveryFields;
        $this->profileChoice = $profileChoice;
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        /** Способ доставки */


        $deliveryChoice = $this->deliveryChoice->fetchAllDelivery();

        $builder
            ->add('delivery', ChoiceType::class, [
                'choices' => $deliveryChoice,
                'choice_value' => function(?DeliveryUid $delivery) {
                    return $delivery?->getValue();
                },

                'choice_label' => function(DeliveryUid $delivery) {
                    return $delivery->getAttr();
                },

                'choice_attr' => function(DeliveryUid $choice) {
                    return [
                        //'checked' => ($choice->equals($deliveryChecked)),
                        'data-price' => $choice->getPrice()?->getValue(),
                        'data-excess' => $choice->getExcess()?->getValue(),
                        'data-currency' => $choice->getCurrency(),
                    ];
                },

                'attr' => ['class' => 'd-flex gap-3'],
                //'help' => $deliveryHelp,
                'label' => false,
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ]);


        /** Координаты на карте */

        /* GPS широта:*/

        $builder->add('latitude', HiddenType::class, ['required' => false, 'attr' => ['data-latitude' => 'true']]);

        $builder->get('latitude')->addModelTransformer(
            new CallbackTransformer(
                function($gps) {
                    return $gps instanceof GpsLatitude ? $gps->getValue() : $gps;
                },
                function($gps) {
                    return new GpsLatitude($gps);
                }
            )
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
                }
            )
        );



        /* Коллекция пользовательских свойств */
        $builder->add('field', CollectionType::class, [
            'entry_type' => Field\OrderDeliveryFieldForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__delivery_field__',
        ]);


        /**
         * Дата доставки заказа
         */
        $builder->add('deliveryDate', DateType::class, [
            'widget' => 'single_text',
            'html5' => false,
            'attr' => ['class' => 'js-datepicker'],
            'required' => false,
            'format' => 'dd.MM.yyyy',
            'input' => 'datetime_immutable',
        ]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($options) {

                /** @var OrderDeliveryDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                //if($data->getDelivery() && $options['user_profile_type'])
                if($options['user_profile_type'])
                {

                    $deliveryChoice = $this->deliveryChoice->fetchDeliveryByProfile($options['user_profile_type']);

                    /** @var DeliveryUid $currentDelivery */
                    $currentDelivery = current($deliveryChoice);

                    $deliveryHelp = $currentDelivery ? $currentDelivery->getOption() : '';
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

                    /** Получаем пользовательские поля */
                    if($deliveryChecked)
                    {
                        $fields = $this->deliveryFields->fetchDeliveryFields($deliveryChecked);

                        $values = $data->getField();

                        //dump($fields);
                        //dd($values);

                        /** @var Field\OrderDeliveryFieldDTO $value */
                        foreach($values as $key => $value)
                        {
                            if(!isset($fields[$key]))
                            {
                                $values->removeElement($value);
                            }

                            if(isset($fields[$key]))
                            {
                                $value->setField($fields[$key]);
                                unset($fields[$key]);
                            }
                        }

                        foreach($fields as $field)
                        {
                            $OrderDeliveryFieldDTO = new Field\OrderDeliveryFieldDTO();
                            $OrderDeliveryFieldDTO->setField($field);
                            $data->addField($OrderDeliveryFieldDTO);
                        }

                        /* Коллекция продукции */
                        $form->add('field', CollectionType::class, [
                            'entry_type' => Field\OrderDeliveryFieldForm::class,
                            'entry_options' => ['label' => false],
                            'label' => false,
                            'by_reference' => false,
                            'allow_delete' => true,
                            'allow_add' => true,
                            'prototype_name' => '__delivery_field__',
                        ]);

                    }

                }
            }
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