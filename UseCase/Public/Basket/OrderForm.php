<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Public\Basket;


use BaksDev\Orders\Order\UseCase\Public\Basket\Add\PublicOrderProductForm;
use BaksDev\Orders\Order\UseCase\Public\Basket\Project\OrderProjectForm;
use BaksDev\Orders\Order\UseCase\Public\Basket\Service\BasketServiceForm;
use BaksDev\Orders\Order\UseCase\Public\Basket\User\OrderUserForm;
use BaksDev\Services\BaksDevServicesBundle;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* Коллекция продукции */
        $builder->add('product', CollectionType::class, [
            'entry_type' => PublicOrderProductForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__product__',
        ]);

        $builder->add('project', OrderProjectForm::class, ['label' => false]);

        $builder->add('comment', TextareaType::class, ['required' => false]);

        $has_services = class_exists(BaksDevServicesBundle::class);

        if($has_services)
        {
            $builder->add('serv', CollectionType::class, [
                'entry_type' => BasketServiceForm::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'by_reference' => false,
                'allow_add' => true,
            ]);
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) {

                /* @var OrderDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                if(false === $data->getProduct()->isEmpty())
                {
                    $form->add('usr', OrderUserForm::class, ['label' => false]);

                    /* Сохранить ******************************************************/
                    $form->add(
                        'order',
                        SubmitType::class,
                        ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']],
                    );
                }

            },
        );


        if($has_services)
        {
            /* Удалить из коллекции НЕ выбранную услугу */
            $builder->addEventListener(
                FormEvents::POST_SUBMIT,
                function(FormEvent $event) {

                    /** @var OrderDTO $data */
                    $data = $event->getData();

                    /** @var ArrayCollection $collection */
                    $collection = $data->getServ();

                    foreach($collection as $BasketServiceDTO)
                    {
                        $selected = $BasketServiceDTO->getSelected();

                        if($selected === false)
                        {
                            /* Удалить элемент коллекции */
                            $data->removeServ($BasketServiceDTO);
                        }
                    }

                });
        }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderDTO::class,
        ]);
    }

}
