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

namespace BaksDev\Orders\Order\UseCase\Admin\Package;

use BaksDev\Orders\Order\Repository\GeocodeAddress\GeocodeAddressInterface;
use BaksDev\Orders\Order\UseCase\Admin\Package\Invariable\PackageOrderInvariableForm;
use BaksDev\Orders\Order\UseCase\Admin\Package\User\Delivery\OrderDeliveryDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PackageOrderForm extends AbstractType
{
    public function __construct(private readonly GeocodeAddressInterface $geocodeAddress) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('invariable', PackageOrderInvariableForm::class);
        $builder->add('product', HiddenType::class);

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function(FormEvent $event): void {


                /** @var PackageOrderDTO $PackageOrderDTO */
                $PackageOrderDTO = $event->getData();
                $form = $event->getForm();

                $PackageOrderInvariableDTO = $PackageOrderDTO->getInvariable();

                $UserProfileUid = $PackageOrderInvariableDTO->getProfile();

                /**
                 * Присваиваем идентификаторы заказа
                 * методы присвоят идентификаторы только в случае, если ранее они не были определены
                 */

                $this->productModifier($form, $UserProfileUid);

            },
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function(FormEvent $event): void {


                /** @var PackageOrderDTO $PackageOrderDTO */
                $PackageOrderDTO = $event->getData();
                $form = $event->getForm();

                $PackageOrderInvariableDTO = $PackageOrderDTO->getInvariable();

                $UserProfileUid = $PackageOrderInvariableDTO->getProfile();


                /**
                 * Присваиваем идентификаторы заказа
                 * методы присвоят идентификаторы только в случае, если ранее они не были определены
                 */

                $this->productModifier($form, $UserProfileUid);

                /** @var OrderDeliveryDTO $Delivery */
                $Delivery = $PackageOrderDTO->getUsr()->getDelivery();

                if($Delivery->getLatitude() && $Delivery->getLongitude())
                {
                    $address = $this->geocodeAddress->fetchGeocodeAddressAssociative($Delivery->getLatitude(), $Delivery->getLongitude());

                    if($address && isset($address['address']))
                    {
                        $Delivery->setAddress($address['address']);
                    }
                }
            },
        );


        //        /**
        //         * Обновляем список доступной продукции на складе
        //         */
        //        $builder->get('invariable')->addEventListener(
        //            FormEvents::POST_SUBMIT,
        //            function(FormEvent $event): void {
        //
        //                /** @var PackageOrderInvariableDTO $PackageOrderInvariableDTO */
        //                $PackageOrderInvariableDTO = $event->getData();
        //                $this->productModifier($event->getForm()->getParent(), $PackageOrderInvariableDTO->getProfile());
        //            }
        //        );


        /* Сохранить */
        $builder->add(
            'package',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );
    }

    public function productModifier(FormInterface $form, ?UserProfileUid $profile): void
    {
        if(is_null($profile))
        {
            return;
        }

        /* Коллекция продукции */
        $form->add('product', CollectionType::class, [
            'entry_type' => Products\PackageOrderProductForm::class,
            'entry_options' => ['label' => false, 'warehouse' => $profile],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__product__',
        ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PackageOrderDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }


}
