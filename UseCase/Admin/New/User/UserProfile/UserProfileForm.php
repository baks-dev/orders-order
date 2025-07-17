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

namespace BaksDev\Orders\Order\UseCase\Admin\New\User\UserProfile;

use App\Module\Materials\Material\Type\Id\MaterialUid;
use BaksDev\Users\Profile\TypeProfile\Repository\TypeProfileChoice\TypeProfileChoiceRepository;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use BaksDev\Users\Profile\UserProfile\Repository\FieldValueForm\FieldValueFormInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserProfileForm extends AbstractType
{
    public function __construct(
        private readonly TypeProfileChoiceRepository $profileChoice,
        private readonly FieldValueFormInterface $fieldValue,
    ) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** Список профилей, доступных администратору (статус Active) */
        $profileChoice = $this->profileChoice->getActiveTypeProfileChoice();
        $profileChoice = iterator_to_array($profileChoice);

        $builder
            ->add('type', ChoiceType::class, [
                'choices' => $profileChoice,
                'choice_value' => function(?TypeProfileUid $type) {
                    return $type?->getValue();
                },

                'choice_label' => function(TypeProfileUid $type) {
                    return $type->getAttr();
                },

                'attr' => ['class' => 'd-flex gap-3'],
                'label' => false,
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ]);

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

                /** @var UserProfileDTO $data */
                $data = $event->getData();
                $form = $event->getForm();


                $fields = [];

                if($data->getType())
                {
                    $fields = $this->fieldValue->get($data->getType());


                    $values = $data->getValue();

                    foreach($values as $key => $value)
                    {
                        if(!isset($fields[$key]))
                        {
                            $values->removeElement($value);
                        }

                        if(isset($fields[$key]))
                        {
                            $value->setField($fields[$key]->getField());
                        }
                    }


                    $form->add('value', CollectionType::class, [
                        'entry_type' => Value\ValueForm::class,
                        'entry_options' => ['label' => false, 'fields' => $data->getType() ? $fields : null],
                        'label' => false,
                        'by_reference' => false,
                        'allow_delete' => true,
                        'allow_add' => true,
                    ]);


                }
            }
        );


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {

                /** @var UserProfileDTO $data */
                $data = $event->getData();

                if($data->getType())
                {
                    $values = $data->getValue();


                    $data->setValue(new ArrayCollection());

                    $fields = $this->fieldValue->get($data->getType());

                    if(empty($fields))
                    {
                        return;
                    }

                    foreach($fields as $key => $field)
                    {
                        $set = $values->get($key);

                        $value = new Value\ValueDTO();
                        $value->setField($field->getField());
                        $value->updSection($field);
                        $value->setValue($set?->getValue());
                        $data->addValue($value);
                    }

                    $form = $event->getForm()->getParent();

                    $form->add(
                        'userProfile',
                        self::class,
                        [
                            'label' => false
                        ]
                    );
                }
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
                'user_profile_type' => null,
            ]
        );
    }

}
