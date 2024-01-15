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

namespace BaksDev\Orders\Order\UseCase\Admin\NewOLD\User\UserProfile;

use App\Module\Materials\Material\Type\Id\MaterialUid;
use BaksDev\Users\Profile\TypeProfile\Repository\TypeProfileChoice\TypeProfileChoice;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use BaksDev\Users\Profile\UserProfile\Repository\FieldValueForm\FieldValueFormDTO;
use BaksDev\Users\Profile\UserProfile\Repository\FieldValueForm\FieldValueFormInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserProfileForm extends AbstractType
{
    private FieldValueFormInterface $fieldValue;

    private TypeProfileChoice $profileChoice;


    public function __construct(
        TypeProfileChoice $profileChoice,
        FieldValueFormInterface $fieldValue,
    )
    {
        $this->fieldValue = $fieldValue;
        $this->profileChoice = $profileChoice;
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('type', HiddenType::class);

        $builder->add('value', CollectionType::class, [
            'entry_type' => Value\ValueForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
        ]);


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($options) {

                dump('PRE_SET_DATA '.$this::class);

                $form = $event->getForm();
                /** @var UserProfileDTO $data */
                $data = $event->getData();

                if($options['user_profile_type'])
                {
                    $data->setType($options['user_profile_type']);
                }

                /** Список профилей, доступных администратору (статус Active) */
                $profileChoice = $this->profileChoice->getActiveTypeProfileChoice();
                $profileChoice = iterator_to_array($profileChoice);

                /* Получаем все поля для заполнения */
                $profileType = $data->getType() ?: current($profileChoice);

                //$data->setType($profileType);

                $fields = $this->fieldValue->get($profileType);



                $form
                    ->add('type', ChoiceType::class, [
                        'choices' => $profileChoice,
                        'choice_value' => function(?TypeProfileUid $type) {
                            return $type?->getValue();
                        },

                        'choice_label' => function(TypeProfileUid $type) {
                            return $type->getAttr();
                        },

                        'choice_attr' => function(TypeProfileUid $choice) use ($profileType) {
                            return ['checked' => ($choice->equals($profileType))];
                        },
                        'attr' => ['class' => 'd-flex gap-3'],
                        'label' => false,
                        'expanded' => false,
                        'multiple' => false,
                        'required' => true,
                    ]);



                if($data->getType())
                {

                    $data->resetValue();
                    $form->remove('value');

                    /** @var FieldValueFormDTO $field */
                    foreach($fields as $field)
                    {
                        $field = end($field);

                        /** Обязательные поля для заполнения */
                        //if($field->isRequired())
                        //{
                            $value = new Value\ValueDTO();
                            $value->setField($field->getField());
                            $value->updSection($field);
                            $data->addValue($value);
                       // }
                    }

                    //dump($data);

                    $form->add('value', CollectionType::class, [
                        'entry_type' => Value\ValueForm::class,
                        'entry_options' => ['label' => false, 'fields' => $fields],
                        'label' => false,
                        'by_reference' => false,
                        'allow_delete' => true,
                        'allow_add' => true,
                    ]);
                }


            }
        );






//                $builder->addEventListener(
//                    FormEvents::POST_SUBMIT,
//                    function (FormEvent $event) : void {
//
//                        dump('POST_SUBMIT '.$this::class);
//                    }
//                );


    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults
        (
            [
                'data_class' => UserProfileDTO::class,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
                'user_profile_type' => null,
            ]
        );
    }

}
