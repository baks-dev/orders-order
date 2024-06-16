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

namespace BaksDev\Orders\Order\UseCase\Admin\New\User\UserProfile\Value;

use BaksDev\Core\Services\Fields\FieldsChoice;
use BaksDev\Users\Profile\UserProfile\Repository\FieldValueForm\FieldValueFormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ValueForm extends AbstractType
{
    private FieldValueFormInterface $fieldValue;

    private FieldsChoice $fieldsChoice;

    public function __construct(
        FieldValueFormInterface $fieldValue,
        FieldsChoice $fieldsChoice,
    )
    {
        $this->fieldValue = $fieldValue;
        $this->fieldsChoice = $fieldsChoice;
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* TextType */
        $builder->add('value', HiddenType::class, ['label' => false]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($options) {

                /** @var ValueDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                $fields = $options['fields'];

                if($data && $fields)
                {

                    $field = $this->fieldValue->getFieldById($data->getField());

                    $fieldType = $this->fieldsChoice->getChoice($field->getType());

                    if($fieldType)
                    {
                        $form->add(
                            'value',
                            $fieldType->form(),
                            [
                                'label' => $field->getFieldName(),
                                'required' => $field->isRequired(),
                                'help' => $field->getFieldDescription(),
                            ]
                        );
                    }
                }
            }
        );


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {

                $data = $event->getData();
                $form = $event->getForm()->getParent();

                $form?->add('value', CollectionType::class, [
                    'entry_type' => self::class,
                    'entry_options' => ['label' => false],
                    'label' => false,
                    'by_reference' => false,
                    'allow_delete' => true,
                    'allow_add' => true,
                ]);


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
