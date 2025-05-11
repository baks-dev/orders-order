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

namespace BaksDev\Orders\Order\UseCase\Public\Basket\User\Payment\Field;

use BaksDev\Core\Services\Fields\FieldsChoice;
use BaksDev\Payment\Type\Field\PaymentFieldUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class OrderPaymentFieldForm extends AbstractType
{
    public function __construct(private readonly FieldsChoice $fieldsChoice) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('field', HiddenType::class);

        $builder->get('field')->addModelTransformer(
            new CallbackTransformer(
                function($field) {
                    return $field instanceof PaymentFieldUid ? $field->getValue() : $field;
                },
                function($field) {
                    return new PaymentFieldUid($field);
                }
            )
        );

        $builder->add('value', HiddenType::class, ['required' => false, 'label' => false]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) {


                /* @var OrderPaymentFieldDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                if($data)
                {

                    $PaymentField = $data->getField();
                    if($PaymentField->getType())
                    {
                        $fieldType = $this->fieldsChoice->getChoice($PaymentField->getType());

                        $form->add(
                            'value',
                            $fieldType->form(),
                            [
                                'label' => $PaymentField->getAttr(),
                                'help' => $PaymentField->getOption(),
                                'required' => $PaymentField->getRequired(),
                                'constraints' => $PaymentField->getRequired() ? [new NotBlank()] : [],
                            ]
                        );
                    }
                }
            }
        );

    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderPaymentFieldDTO::class,
        ]);
    }

}
