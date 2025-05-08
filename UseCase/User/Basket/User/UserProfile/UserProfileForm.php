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

namespace BaksDev\Orders\Order\UseCase\User\Basket\User\UserProfile;

use BaksDev\Users\Profile\TypeProfile\Repository\TypeProfileChoice\TypeProfileChoiceRepository;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use BaksDev\Users\Profile\UserProfile\Repository\FieldValueForm\FieldValueFormDTO;
use BaksDev\Users\Profile\UserProfile\Repository\FieldValueForm\FieldValueFormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class UserProfileForm extends AbstractType
{
    public function __construct(
        private readonly TypeProfileChoiceRepository $profileChoice,
        private readonly FieldValueFormInterface $fieldValue,
        private readonly TokenStorageInterface $tokenStorage
    ) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) {

                $form = $event->getForm();
                /** @var UserProfileDTO $data */
                $data = $event->getData();


                $profileChoice = $this->profileChoice->getPublicTypeProfileChoice();
                $profileChoice = iterator_to_array($profileChoice);

                $User = $this->tokenStorage->getToken()?->getUser();

                /** Если пользователь не авторизован - предоставляем выбор тип профиля и поля для заполнения */
                if($User)
                {
                    $this->fieldValue->userFilter($User);
                }
                else
                {

                    /* Получаем все поля для заполнения */
                    $profileType = $data->getType() ?: current($profileChoice);

                    if($profileType)
                    {
                        $data->setType($profileType);
                    }

                    $form
                        ->add('type', ChoiceType::class, [
                            'choices' => $profileChoice,
                            'choice_value' => function(?TypeProfileUid $type) {
                                return $type?->getValue();
                            },

                            'choice_label' => function(TypeProfileUid $type) {
                                return $type->getOption();
                            },

                            'choice_attr' => function(TypeProfileUid $choice) use ($profileType) {
                                return ['checked' => ($choice->equals($profileType))];
                            },
                            'attr' => ['class' => 'd-flex gap-3'],
                            'label' => false,
                            'expanded' => true,
                            'multiple' => false,
                            'required' => true,
                        ]);
                }

                $fields = $data->getType() ? $this->fieldValue->get($data->getType()) : [];

                if(empty($fields))
                {
                    return;
                }

                $data->resetValue();
                $form->remove('value');

                /** @var FieldValueFormDTO $field */
                foreach($fields as $field)
                {
                    //$field = end($field);

                    /** Обязательные поля для заполнения */

                    $value = new Value\ValueDTO();
                    $value->setField($field->getField());
                    $value->updSection($field);
                    $data->addValue($value);

                }

                $form->add('value', CollectionType::class, [
                    'entry_type' => Value\ValueForm::class,
                    'entry_options' => ['label' => false, 'fields' => $fields],
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
                'data_class' => UserProfileDTO::class,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
            ]
        );
    }

}
