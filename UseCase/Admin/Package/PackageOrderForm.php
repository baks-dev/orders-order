<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Admin\Package;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Orders\Order\Repository\GeocodeAddress\GeocodeAddressInterface;
use BaksDev\Orders\Order\UseCase\Admin\Package\User\Delivery\OrderDeliveryDTO;
use BaksDev\Users\Address\Services\GeocodeDistance;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileChoice\UserProfileChoiceInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PackageOrderForm extends AbstractType
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

        //$CurrentUser = $builder->getData()->getCurrent();

        /**
         * Все профили пользователя
         */
        $profiles = $this->userProfileChoice
            ->getActiveUserProfile($this->profileTokenStorage->getUser());


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($profiles): void {

                /** @var PackageOrderDTO $PackageOrderDTO */
                $PackageOrderDTO = $event->getData();
                $form = $event->getForm();

                $UserProfileUid = $this->profileTokenStorage->getProfile();

                /**
                 * Присваиваем идентификаторы заказа
                 * методы присвоят идентификаторы только в случае, если ранее они не были определены
                 */
                $PackageOrderInvariableDTO = $PackageOrderDTO->getInvariable();
                $PackageOrderInvariableDTO->setUsr($this->profileTokenStorage->getUser());
                $PackageOrderInvariableDTO->setProfile($UserProfileUid);
                $this->productModifier($form, $UserProfileUid);

                //                /**
                //                 * Если в списке присутствует только один профиль - присваиваем профиль активного пользователя
                //                 */
                //                if(count($profiles) === 1)
                //                {
                //                    //$PackageOrderDTO->setProfile($UserProfileUid);
                //
                //                    $PackageOrderInvariableDTO->setProfile($UserProfileUid);
                //                    $this->productModifier($form, $UserProfileUid);
                //                }


                ///$PackageOrderDTO->setProfile($UserProfileUid);
                ///$PackageOrderInvariableDTO->setProfile($UserProfileUid);


                /** @var OrderDeliveryDTO $Delivery */
                $Delivery = $PackageOrderDTO->getUsr()->getDelivery();

                if($Delivery->getLatitude() && $Delivery->getLongitude())
                {
                    $address = $this->geocodeAddress->fetchGeocodeAddressAssociative($Delivery->getLatitude(), $Delivery->getLongitude());
                    //$pickup = $this->contactCallByGeocode->existContactCallByGeocode($Delivery->getLatitude(), $Delivery->getLongitude());

                    if($address)
                    {
                        $Delivery->setAddress($address['address']);
                    }

                    //$Delivery->setPickup($pickup);

                    $distance = null;

                    /* Поиск ближайшего склада */
                    /** @var UserProfileUid $profile */
                    foreach($profiles as $profile)
                    {
                        if(!$profile->getOption() || !$profile->getProperty())
                        {
                            continue;
                        }

                        ///$warehouseGeocode = explode(',', $profile->getOption());

                        /**
                         * @var GpsLatitude $profileLatitude
                         * @var GpsLongitude $profileLongitude
                         */
                        $profileLatitude = $profile->getOption();
                        $profileLongitude = $profile->getProperty();

                        $this->geocodeDistance
                            ->fromLatitude((float) $Delivery->getLatitude()->getValue())
                            ->fromLongitude((float) $Delivery->getLongitude()->getValue())
                            ->toLatitude($profileLatitude->getFloat())
                            ->toLongitude($profileLongitude->getFloat());


                        $geocodeDistance = $this->geocodeDistance->getDistance();

                        /**
                         * Если геолокация заказа равна геолокации профиля - присваиваем профиль
                         */
                        if($this->geocodeDistance->isEquals())
                        {
                            $PackageOrderDTO->setProfile($UserProfileUid);
                            $PackageOrderInvariableDTO->setProfile($UserProfileUid);
                            $this->nearestWarehouse = $UserProfileUid; // Ближайший
                            $this->pickupWarehouse = $UserProfileUid; // Пункт выдачи заказов
                            $this->productModifier($form, $UserProfileUid);

                            break;
                        }

                        if($distance === null || $geocodeDistance < $distance)
                        {
                            $distance = $geocodeDistance;
                            //$PackageOrderDTO->setProfile($UserProfileUid);
                            $PackageOrderInvariableDTO->setProfile($UserProfileUid);
                            $this->nearestWarehouse = $UserProfileUid; // Ближайший
                            $this->productModifier($form, $UserProfileUid);
                        }
                    }
                }

                // Еси не указана геолокация доставки - присваиваем по умолчанию склад активного пользователя
                //                else
                //                {
                //                    $PackageOrderDTO->setProfile($UserProfileUid);
                //                    $PackageOrderInvariableDTO->setProfile($UserProfileUid);
                //                }
            },
        );

        /* Коллекция продукции */
        $builder->add('product', CollectionType::class, [
            'entry_type' => Products\PackageOrderProductForm::class,
            /*'entry_options' => [
                'label' => false,
                'usr' => $CurrentUser
            ],*/
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__product__',
        ]);


        // Склад
        $builder
            ->add('profile', ChoiceType::class, [
                'choices' => $profiles,
                'choice_value' => function(?UserProfileUid $profile) {
                    return $profile?->getValue();
                },
                'choice_label' => function(UserProfileUid $profile) {
                    return $profile->getAttr();
                },

                'label' => false,
                'required' => true,
            ]);


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
                'choice_attr' => function($warehouse) {

                    if($this->pickupWarehouse && !$warehouse->equals($this->pickupWarehouse))
                    {
                        return ['disabled' => 'disabled'];
                    }

                    return [];
                }

            ]
        );

        $builder->get('profile')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {
                $PackageOrderDTO = (string) $event->getData();
                $this->productModifier($event->getForm()->getParent(), new UserProfileUid($PackageOrderDTO));
            }
        );

        /* Сохранить */
        $builder->add(
            'package',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PackageOrderDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }

    public function productModifier(FormInterface $form, UserProfileUid $warehouse): void
    {
        /* Коллекция продукции */
        $form->add('product', CollectionType::class, [
            'entry_type' => Products\PackageOrderProductForm::class,
            'entry_options' => ['label' => false, 'warehouse' => $warehouse],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__product__',
        ]);
    }
}
