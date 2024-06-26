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

namespace BaksDev\Orders\Order\UseCase\User\Basket\User\UserProfile\Value;

use BaksDev\Core\Services\Fields\FieldsChoice;
use BaksDev\Users\Profile\UserProfile\Repository\FieldValueForm\FieldValueFormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ValueForm extends AbstractType
{
    public function __construct(
        private readonly FieldValueFormInterface $fieldValue,
        private readonly FieldsChoice $fieldsChoice,
    ) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {

                /* @var ValueDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                if($data)
                {
                    $field = $this->fieldValue->getFieldById($data->getField());

                    $fieldType = $this->fieldsChoice->getChoice($field->getType());

                    if($fieldType)
                    {
                        $options['label'] = $field->getFieldName();
                        $options['required'] = $field->isRequired();
                        $options['help'] = $field->getFieldDescription();

                        if($field->isRequired())
                        {
                            $options['constraints'][] = new Assert\NotBlank();
                        }

                        if($fieldType->constraints())
                        {
                            foreach($fieldType->constraints() as $constraint)
                            {
                                $options['constraints'][] = $constraint;
                            }
                        }

                        $form->add('value', $fieldType->form(), $options);
                    }
                }
            }
        );
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ValueDTO::class,
                'fields' => null,
            ]
        );
    }
}
