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

namespace BaksDev\Orders\Order\UseCase\Admin\New\User\Payment;

use BaksDev\Megamarket\Orders\BaksDevMegamarketOrdersBundle;
use BaksDev\Megamarket\Orders\Type\PaymentType\TypePaymentDbsMegamarket;
use BaksDev\Megamarket\Orders\Type\PaymentType\TypePaymentFbsMegamarket;
use BaksDev\Megamarket\Orders\Type\ProfileType\TypeProfileDbsMegamarket;
use BaksDev\Megamarket\Orders\Type\ProfileType\TypeProfileFbsMegamarket;
use BaksDev\Orders\Order\Repository\FieldByPaymentChoice\FieldByPaymentChoiceInterface;
use BaksDev\Orders\Order\Repository\PaymentByTypeProfileChoice\PaymentByTypeProfileChoiceInterface;
use BaksDev\Ozon\Orders\BaksDevOzonOrdersBundle;
use BaksDev\Ozon\Orders\Type\PaymentType\TypePaymentDbsOzon;
use BaksDev\Ozon\Orders\Type\PaymentType\TypePaymentFbsOzon;
use BaksDev\Ozon\Orders\Type\ProfileType\TypeProfileDbsOzon;
use BaksDev\Ozon\Orders\Type\ProfileType\TypeProfileFbsOzon;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Yandex\Market\Orders\BaksDevYandexMarketOrdersBundle;
use BaksDev\Yandex\Market\Orders\Type\PaymentType\TypePaymentDbsYaMarket;
use BaksDev\Yandex\Market\Orders\Type\PaymentType\TypePaymentFbsYandex;
use BaksDev\Yandex\Market\Orders\Type\ProfileType\TypeProfileDbsYaMarket;
use BaksDev\Yandex\Market\Orders\Type\ProfileType\TypeProfileFbsYaMarket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderPaymentForm extends AbstractType
{
    public function __construct(
        private readonly PaymentByTypeProfileChoiceInterface $paymentChoice,
        private readonly FieldByPaymentChoiceInterface $paymentFields
    ) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $paymentChoice = $this->paymentChoice->fetchAllPayment();

        $builder
            ->add('payment', ChoiceType::class, [
                'choices' => $paymentChoice,
                'choice_value' => function(mixed $payment) {
                    $payment = $payment ? new PaymentUid((string) $payment) : null;
                    return $payment?->getValue();
                },

                'choice_label' => function(PaymentUid $payment) {
                    return $payment->getAttr();
                },

                'choice_attr' => function(PaymentUid $choice) {
                    return [
                        //'checked' => ($choice->equals($deliveryChecked)),
                        //'data-price' => $choice->getPrice()?->getValue(),
                        //'data-excess' => $choice->getExcess()?->getValue(),
                        //'data-currency' => $choice->getCurrency(),
                    ];
                },

                'attr' => ['class' => 'd-flex gap-3'],
                //'help' => $deliveryHelp,
                'label' => false,
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ]);


        //$builder->add('payment', HiddenType::class);
        //$builder->add('payment', HiddenType::class);

        /* Коллекция пользовательских свойств */
        $builder->add('field', CollectionType::class, [
            'entry_type' => Field\OrderPaymentFieldForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__payment_field__',
        ]);


        $builder->get('payment')->addModelTransformer(
            new CallbackTransformer(
                function($payment) {
                    return $payment instanceof PaymentUid ? $payment->getValue() : $payment;
                },
                function($payment) {

                    return new PaymentUid($payment);
                }
            )
        );


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($options) {

                if($options['user_profile_type'])
                {
                    /** @var OrderPaymentDTO $data */
                    $data = $event->getData();
                    $form = $event->getForm();

                    //dd($options['user_profile_type']);


                    /**
                     * Присваиваем по умолчанию способы доставки соответствующие профилю
                     */

                    /** Если выбрана доставка Ozon */
                    if(class_exists(BaksDevOzonOrdersBundle::class))
                    {
                        $TypeProfileOzon = match (true)
                        {
                            TypeProfileFbsOzon::equals($options['user_profile_type']) => TypePaymentFbsOzon::TYPE,
                            TypeProfileDbsOzon::equals($options['user_profile_type']) => TypePaymentDbsOzon::TYPE,
                            default => false,
                        };

                        if($TypeProfileOzon)
                        {
                            $data->setPayment(new PaymentUid($TypeProfileOzon));
                        }
                    }

                    /** Если выбрана доставка Yandex Market */
                    if(class_exists(BaksDevYandexMarketOrdersBundle::class))
                    {
                        $TypeDeliveryYaMarket = match (true)
                        {
                            TypeProfileFbsYaMarket::equals($options['user_profile_type']) => TypePaymentFbsYandex::TYPE,
                            TypeProfileDbsYaMarket::equals($options['user_profile_type']) => TypePaymentDbsYaMarket::TYPE,
                            default => false,
                        };

                        if($TypeDeliveryYaMarket)
                        {
                            $data->setPayment(new PaymentUid($TypeDeliveryYaMarket));
                        }
                    }

                    /** Если выбрана доставка Magamarket */
                    if(class_exists(BaksDevMegamarketOrdersBundle::class))
                    {
                        $TypeDeliveryMegamarket = match (true)
                        {
                            TypeProfileFbsMegamarket::equals($options['user_profile_type']) => TypePaymentFbsMegamarket::TYPE,
                            TypeProfileDbsMegamarket::equals($options['user_profile_type']) => TypePaymentDbsMegamarket::TYPE,
                            default => false,
                        };

                        if($TypeDeliveryMegamarket)
                        {
                            $data->setPayment(new PaymentUid($TypeDeliveryMegamarket));
                        }
                    }


                    $paymentChoice = $this->paymentChoice->fetchPaymentByProfile($options['user_profile_type']);

                    /** @var PaymentUid $currentPayment */
                    $currentPayment = current($paymentChoice);

                    $paymentHelp = $currentPayment ? $currentPayment->getAttr() : '';
                    $paymentChecked = $currentPayment;

                    /** @var PaymentUid $Payment */
                    $Payment = $data->getPayment();

                    if($Payment)
                    {
                        $paymentCheckedFilter = array_filter($paymentChoice, function($v, $k) use ($Payment) {
                            return $v->equals($Payment);
                        }, ARRAY_FILTER_USE_BOTH);

                        //dd($paymentCheckedFilter);

                        if($paymentCheckedFilter)
                        {
                            $paymentChecked = current($paymentCheckedFilter);

                            /* Присваиваем способу отплаты */
                            //$data->setPayment($paymentChecked->getEvent());

                            $paymentHelp = $paymentChecked?->getAttr();
                        }
                    }

                    $form
                        ->add('payment', ChoiceType::class, [
                            'choices' => $paymentChoice,
                            'choice_value' => function(?PaymentUid $payment) {
                                return $payment?->getValue();
                            },

                            'choice_label' => function(PaymentUid $payment) {
                                return $payment->getOption();
                            },

                            'choice_attr' => function(PaymentUid $choice) use ($paymentChecked) {
                                return ['checked' => ($choice->equals($paymentChecked))];
                            },

                            'attr' => ['class' => 'd-flex gap-3'],
                            'help' => $paymentHelp,
                            'label' => false,
                            'expanded' => true,
                            'multiple' => false,
                            'required' => true,
                        ]);

                    /** Получаем пользовательские поля */
                    if($paymentChecked)
                    {

                        $fields = $this->paymentFields->fetchPaymentFields($paymentChecked);

                        $values = $data->getField();

                        foreach($values as $key => $value)
                        {
                            if(!isset($fields[$key]))
                            {
                                $values->removeElement($value);
                            }

                            if(isset($fields[$key]))
                            {
                                //dd($fields[$key]);

                                $value->setField($fields[$key]);
                                unset($fields[$key]);
                            }
                        }

                        foreach($fields as $field)
                        {
                            $OrderPaymentFieldDTO = new Field\OrderPaymentFieldDTO();
                            $OrderPaymentFieldDTO->setField($field);
                            $data->addField($OrderPaymentFieldDTO);
                        }

                        /* Коллекция продукции */
                        $form->add('field', CollectionType::class, [
                            'entry_type' => Field\OrderPaymentFieldForm::class,
                            'entry_options' => ['label' => false],
                            'label' => false,
                            'by_reference' => false,
                            'allow_delete' => true,
                            'allow_add' => true,
                            'prototype_name' => '__payment_field__',
                        ]);

                    }

                }
            }
        );

    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderPaymentDTO::class,
            'user_profile_type' => null,
        ]);
    }

}
