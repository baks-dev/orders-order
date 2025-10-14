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

namespace BaksDev\Orders\Order\UseCase\Public\Basket\Service;

use BaksDev\Orders\Order\Repository\Services\AllServicePeriodByDate\AllServicePeriodByDateInterface;
use BaksDev\Orders\Order\Repository\Services\AllServicePeriodByDate\AllServicePeriodByDateResult;
use BaksDev\Orders\Order\Repository\Services\OneServiceById\OneServiceByIdInterface;
use BaksDev\Orders\Order\Type\OrderService\Period\ServicePeriodUid;
use BaksDev\Orders\Order\Type\OrderService\Service\ServiceUid;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BasketServiceForm extends AbstractType
{
    public function __construct(
        private readonly AllServicePeriodByDateInterface $allServicePeriodRepository,
        private readonly OneServiceByIdInterface $oneServiceRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('selected', CheckboxType::class, ['required' => false]);

        $builder->add('serv', HiddenType::class, [
            'required' => true,
        ]);

        $builder->get('serv')
            ->addModelTransformer(
                new CallbackTransformer(
                    function($serv) {
                        return $serv instanceof ServiceUid ? $serv->getValue() : $serv;
                    },
                    function($serv) {
                        return new ServiceUid($serv);
                    },
                ),
            );

        $builder->add('name', HiddenType::class, [
            'required' => true,
        ]);

        $builder->add('preview', HiddenType::class, [
            'required' => true,
        ]);

        $builder->add('money', HiddenType::class, [
            'required' => true,
        ]);

        $builder->get('money')
            ->addModelTransformer(
                new CallbackTransformer(
                    function($price) {
                        return $price instanceof Money ? $price->getValue() : $price;
                    },
                    function($price) {
                        return new Money($price);
                    },
                ),
            );


        $builder->add('date', DateType::class, [
            'widget' => 'single_text',
            'label' => false,
            'html5' => false,
            'format' => 'dd.MM.yyyy',
            'input' => 'datetime_immutable',
            'attr' => ['class' => 'js-datepicker'],
            'required' => false,
        ]);

        $builder->add('period', HiddenType::class, [
            'required' => false,
        ]);

        $builder->get('period')
            ->addModelTransformer(
                new CallbackTransformer(
                    function($period) {
                        return $period instanceof ServicePeriodUid ? $period->getValue() : $period;
                    },
                    function($period) {
                        return new ServicePeriodUid($period);
                    },
                ),
            );


        /* Слушатель события PRE_SET_DATA */
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if($data instanceof BasketServiceDTO)
                {

                    /* Получить услугу */
                    $serviceUuid = $data->getServ();

                    $service = $this->oneServiceRepository->find($serviceUuid);

                    $form->add('name', HiddenType::class, [
                        'empty_data' => $service->getName() !== null ? $service->getName() : null,
                    ]);

                    /**
                     * Получить период по выбранной дате
                     */

                    if($data->getDate())
                    {
                        $periods = $this->allServicePeriodRepository
                            ->byDate($data->getDate())
                            ->findAll($data->getServ());


                        $periodList = iterator_to_array($periods);

                        $periodChoice = array_map(function(AllServicePeriodByDateResult $period) {

                            $from = $period->getFrom();
                            $to = $period->getTo();

                            $data = [
                                'date' => $from->format('H:i').'-'.$to->format('H:i'),
                                'usage' => $period->isOrderServiceActive()
                            ];

                            return new ServicePeriodUid($period->getPeriodId(), $data);
                        }, $periodList);


                        $form->add('period', ChoiceType::class, [
                            'choices' => $periodChoice,
                            'choice_value' => function(?ServicePeriodUid $period) {

                                return $period?->getValue();
                            },
                            'choice_label' => function(ServicePeriodUid $period) {
                                return $period->getParams('date').($period->getParams('usage') === true ? ' - забронировано' : '');
                            },

                            'choice_attr' => function($choice) {
                                return $choice->getParams('usage') === true ? ['disabled' => 'disabled'] : [];
                            },

                            'attr' => ['data-name' => $service->getName()],

                            'label' => 'Период',
                            'expanded' => false,
                            'multiple' => false,
                            'required' => true,
                        ]);
                    }
                }
            }
        );

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BasketServiceDTO::class,
        ]);
    }
}