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

namespace BaksDev\Orders\Order\UseCase\Admin\Package\Invariable;


use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Orders\Order\Repository\GeocodeAddress\GeocodeAddressInterface;
use BaksDev\Users\Address\Services\GeocodeDistance;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileChoice\UserProfileChoiceInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PackageOrderInvariableForm extends AbstractType
{

    /** Ближайший склад */
    private ?UserProfileUid $nearestWarehouse = null;

    /** Склад - пункт выдачи */
    private ?UserProfileUid $pickupWarehouse = null;

    public function __construct(
        private readonly UserProfileChoiceInterface $userProfileChoice,
        private readonly GeocodeAddressInterface $geocodeAddress,
        private readonly GeocodeDistance $geocodeDistance,
        private readonly UserProfileTokenStorageInterface $profileTokenStorage,
    ) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        /**
         * Идентификатор пользователя
         */

        $builder->add('usr', HiddenType::class);

        $builder->get('usr')->addModelTransformer(
            new CallbackTransformer(
                function(?UserUid $user) {
                    return $user instanceof UserUid ? $user->getValue() : $user;
                },
                function(?string $user) {
                    return $user ? new UserUid($user) : null;
                },
            ),
        );


        /**
         * Все профили пользователя
         */

        $profiles = $this->userProfileChoice
            ->getActiveUserProfile($this->profileTokenStorage->getUser());

        /* Склад назначения */
        $builder->add(
            'profile',
            ChoiceType::class,
            [
                'choices' => $profiles,
                'choice_value' => function(?UserProfileUid $profile) {
                    return $profile?->getValue();
                },
                'choice_label' => function(UserProfileUid $warehouse) {

                    /** Если склад - пункт выдачи заказов */
                    if($this->pickupWarehouse && $warehouse->equals($this->pickupWarehouse))
                    {
                        return $warehouse->getAttr().' (Пункт выдачи заказов)';
                    }

                    /** Если склад ближе к клиенту */
                    if($this->nearestWarehouse && $warehouse->equals($this->nearestWarehouse))
                    {
                        return $warehouse->getAttr().' (Ближайший к клиенту)';
                    }

                    return $warehouse->getAttr();
                },

                'label' => false,
                'required' => false,
                //'disabled' => true,
                'choice_attr' => function($warehouse) {

                    if($this->pickupWarehouse && !$warehouse->equals($this->pickupWarehouse))
                    {
                        return ['disabled' => 'disabled'];
                    }

                    return [];
                }

            ]
        );


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event): void {

                /** @var PackageOrderInvariableDTO $PackageOrderInvariableDTO */
                //$PackageOrderDTO = $event->getData();
                $PackageOrderInvariableDTO = $event->getData();


                /**
                 * Присваиваем идентификаторы заказа
                 * методы присвоят идентификаторы только в случае, если ранее они не были определены
                 */

                $PackageOrderInvariableDTO->setUsr($this->profileTokenStorage->getUser());
                $PackageOrderInvariableDTO->setProfile($this->profileTokenStorage->getProfile());


            },
        );


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PackageOrderInvariableDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}