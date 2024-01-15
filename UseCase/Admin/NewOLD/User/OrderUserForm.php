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

namespace BaksDev\Orders\Order\UseCase\Admin\NewOLD\User;

use BaksDev\Users\Profile\TypeProfile\Repository\TypeProfileChoice\TypeProfileChoice;
use BaksDev\Users\Profile\UserProfile\Repository\CurrentUserProfile\CurrentUserProfileInterface;
use BaksDev\Users\Profile\UserProfile\Repository\FieldValueForm\FieldValueFormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

final class OrderUserForm extends AbstractType
{
//	private CurrentUserProfileInterface $currentUserProfile;
//
//
//	public function __construct(
//
//		CurrentUserProfileInterface $currentUserProfile,
//	)
//	{
//		$this->currentUserProfile = $currentUserProfile;
//	}


    private TypeProfileChoice $profileChoice;
    private FormBuilderInterface $builder;

    public function __construct(
        TypeProfileChoice $profileChoice
    )
    {
        $this->profileChoice = $profileChoice;
    }


	public function buildForm(FormBuilderInterface $builder, array $options) : void
	{
        $this->builder = $builder;

          //$builder->add('userProfile', HiddenType::class);

        $builder->add('userProfile',
            UserProfile\UserProfileForm::class, [
                'label' => false,
                'constraints' => [new Valid()],
            ]
        );

        $this->builder->add('payment', Payment\OrderPaymentForm::class, ['label' => false,]);

        $this->builder->add('delivery', Delivery\OrderDeliveryForm::class, ['label' => false,]);


//        $builder->add('payment', HiddenType::class);
//        $builder->add('delivery', HiddenType::class);

        $builder->addEventListener(
			FormEvents::PRE_SET_DATA,
			function(FormEvent $event) use ($options) {

				/** @var OrderUserDTO $data */
				$data = $event->getData();
				$form = $event->getForm();

                if(!$data->getUserProfile()?->getType())
                {
                    return;
                }


                dump('PRE_SET_DATA '.$this::class);
                dump($data->getUserProfile()?->getType());


                $userProfileType =  $options['data_user_profile_type'];

                if($userProfileType)
                {
                    $data->setProfile($userProfileType);

                    dd($userProfileType);
                }


//
//                if(!$userProfileType)
//                {
//                    $profileChoice = $this->profileChoice->getActiveTypeProfileChoice();
//                    $profileChoice->current();
//                    $userProfileType = $profileChoice->current();
//                }

                $form->add('userProfile',
                    UserProfile\UserProfileForm::class, [
                        'label' => false,
                        'user_profile_type' => $userProfileType,
                    ]
                );


                $form->add('payment',
                    Payment\OrderPaymentForm::class,
                    [
                        'label' => false,
                        'user_profile_type' => $userProfileType,
                    ]
                );

                $form->add('delivery',
                    Delivery\OrderDeliveryForm::class,
                    [
                        'label' => false,
                        'user_profile_type' => $userProfileType,
                    ]
                );


                return;





                //dump('PRE_SET_DATA '.self::class);

                //dump($data);

                $userProfileType = $data->getUserProfile()?->getType();

                if(!$userProfileType)
                {
                    /** Список профилей, доступных администратору (статус Active) */
                    $profileChoice = $this->profileChoice->getActiveTypeProfileChoice();
                    $profileChoice = iterator_to_array($profileChoice);

                    $data->getUserProfile()?->setType(current($profileChoice));
                    $userProfileType = $data->getUserProfile()?->getType();

                    //dump($userProfileType);

                    $form->add('userProfile',
                        UserProfile\UserProfileForm::class, [
                            'label' => false,
                            'constraints' => [new Valid()],
                        ]
                    );

                    $form->add('payment',
                        Payment\OrderPaymentForm::class,
                        [
                            'label' => false,
                            'user_profile_type' => $userProfileType,
                        ]
                    );

                    $form->add('delivery',
                        Delivery\OrderDeliveryForm::class,
                        [
                            'label' => false,
                            'user_profile_type' => $userProfileType,
                        ]
                    );

                }

			}
		);

//        $builder->get('delivery')->addEventListener(
//            FormEvents::POST_SUBMIT,
//            function (FormEvent $event): void {
//
//                $form = $event->getForm()->getParent();
//
//                dump('POST_SUBMIT delivery');
//
//
//
////                if($form)
////                {
////                    $form->add('usr', User\OrderUserForm::class,
////                        [
////                            'label' => false
////                        ]
////                    );
////                }
//
//                //$formModifier($event->getForm()->getParent());
//            }
//        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) : void {

                $form = $event->getForm()->getParent(); //->getParent();

                /** @var OrderUserDTO $data */

                $data = $event->getData();

                dump('POST_SUBMIT '.$this::class);
                dump($data);

//                if($data->getUserProfile()->getType())
//                {
//                    $form->add('usr', $this::class,
//                        [
//                            'label' => false,
//                            'data_user_profile_type' => $data->getUserProfile()->getType(),
//
//                        ]
//                    );
//                }

            }
        );

	}


	public function configureOptions(OptionsResolver $resolver) : void
	{
		$resolver->setDefaults([
			'data_class' => OrderUserDTO::class,
            'data_user_profile_type' => null
		]);
	}

}