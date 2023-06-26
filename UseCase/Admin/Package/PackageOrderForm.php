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

namespace BaksDev\Orders\Order\UseCase\Admin\Package;

use BaksDev\Contacts\Region\Repository\ContactCallByGeocode\ContactCallByGeocodeInterface;
use BaksDev\Contacts\Region\Repository\WarehouseChoice\WarehouseChoiceInterface;
use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Orders\Order\UseCase\Admin\Package\User\Delivery\OrderDeliveryDTO;
use BaksDev\Users\Address\Repository\GeocodeAddress\GeocodeAddressInterface;
use BaksDev\Users\Address\Services\GeocodeDistance;
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
    private WarehouseChoiceInterface $warehouseChoice;

    private GeocodeAddressInterface $geocodeAddress;

    private GeocodeDistance $geocodeDistance;

    /** Ближайший склад */
    private ?ContactsRegionCallConst $nearestWarehouse = null;

    /** Склад - пункт выдачи */
    private ?ContactsRegionCallConst $pickupWarehouse = null;

    /** Выбранный склад */
    private ?ContactsRegionCallConst $currentWarehouse = null;

    private ContactCallByGeocodeInterface $contactCallByGeocode;

    public function __construct(
        WarehouseChoiceInterface $warehouseChoice,
        GeocodeAddressInterface $geocodeAddress,
        GeocodeDistance $geocodeDistance,
        ContactCallByGeocodeInterface $contactCallByGeocode,
    ) {
        $this->warehouseChoice = $warehouseChoice;
        $this->geocodeAddress = $geocodeAddress;
        $this->geocodeDistance = $geocodeDistance;
        $this->contactCallByGeocode = $contactCallByGeocode;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $warehouses = $this->warehouseChoice->fetchAllWarehouse();

        $this->currentWarehouse = (count($warehouses) === 1) ? current($warehouses) : null;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($warehouses): void {
                /** @var PackageOrderDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                if ($this->currentWarehouse)
                {
                    $data->setWarehouse($this->currentWarehouse);
                }

                /** @var OrderDeliveryDTO $Delivery */
                $Delivery = $data->getUsers()->getDelivery();

                if ($Delivery->getLatitude() && $Delivery->getLongitude())
                {
                    $address = $this->geocodeAddress->fetchGeocodeAddressAssociative($Delivery->getLatitude(), $Delivery->getLongitude());
                    $pickup = $this->contactCallByGeocode->existContactCallByGeocode($Delivery->getLatitude(), $Delivery->getLongitude());

                    if ($address)
                    {
                        $Delivery->setAddress($address['address']);
                    }

                    $Delivery->setPickup($pickup);

                    $distance = null;

                    /* Поиск ближайшего склада */
                    foreach ($warehouses as $warehouse)
                    {
                        $warehouseGeocode = explode(',', $warehouse->getOption());

                        $this->geocodeDistance
                            ->fromLatitude((float) $Delivery->getLatitude()->getValue())
                            ->fromLongitude((float) $Delivery->getLongitude()->getValue())
                            ->toLatitude((float) $warehouseGeocode[0])
                            ->toLongitude((float) $warehouseGeocode[1]);

                        if ($this->geocodeDistance->isEquals())
                        {
                            $data->setWarehouse($warehouse);
                            $this->nearestWarehouse = $warehouse; // Ближайший
                            $this->pickupWarehouse = $warehouse; // Пункт выдачи заказов
                            $this->productModifier($event->getForm(), $warehouse);

                            break;
                        }

                        $geocodeDistance = $this->geocodeDistance->getDistance();

                        if ($geocodeDistance < $distance || $distance === null)
                        {
                            $distance = $geocodeDistance;
                            $data->setWarehouse($warehouse);
                            $this->nearestWarehouse = $warehouse; // Ближайший
                            $this->productModifier($event->getForm(), $warehouse);
                        }
                    }
                }
            },
        );

        /* Коллекция продукции */
        $builder->add('product', CollectionType::class, [
            'entry_type' => Products\PackageOrderProductForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__product__',
        ]);

        /* Склад назначения */
        $builder->add(
            'warehouse',
            ChoiceType::class,
            [
                'choices' => $warehouses,
                'choice_value' => function (?ContactsRegionCallConst $warehouse) {
                    return $warehouse?->getValue();
                },
                'choice_label' => function (ContactsRegionCallConst $warehouse) {
                    if ($this->pickupWarehouse && $warehouse->equals($this->pickupWarehouse))
                    {
                        return $warehouse->getAttr().' (Пункт выдачи заказов)';
                    }

                    if ($this->nearestWarehouse && $warehouse->equals($this->nearestWarehouse))
                    {
                        return $warehouse->getAttr().' (ближайший)';
                    }

                    return $warehouse->getAttr();
                },

                'label' => false,
                'required' => false,
                'choice_attr' => function ($warehouse) {
//                    if ($this->pickupWarehouse && !$warehouse->equals($this->pickupWarehouse))
//                    {
//                        return ['disabled' => 'disabled'];
//                    }

                    return [];
                }

            ]
        );

        $builder->get('warehouse')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                /** @var PackageOrderDTO $data */
                $data = $event->getData();
                $this->productModifier($event->getForm()->getParent(), new ContactsRegionCallConst($data));
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

    public function productModifier(FormInterface $form, ContactsRegionCallConst $warehouse): void
    {
        //dd($warehouse);
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
