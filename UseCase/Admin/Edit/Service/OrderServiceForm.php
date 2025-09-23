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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\Service;

use BaksDev\Orders\Order\Repository\Services\AllServicePeriodByDate\AllServicePeriodByDateInterface;
use BaksDev\Orders\Order\Repository\Services\AllServicePeriodByDate\AllServicePeriodByDateResult;
use BaksDev\Orders\Order\Type\OrderService\Period\ServicePeriodUid;
use BaksDev\Orders\Order\Type\OrderService\Service\ServiceUid;
use BaksDev\Reference\Money\Type\Money;
use DateTimeImmutable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderServiceForm extends AbstractType
{
    public function __construct(
        private readonly AllServicePeriodByDateInterface $allServicePeriodByDateRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

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

        $builder->add('money', MoneyType::class,
            [
                'attr' => [
                    'data-min' => new Money(1)
                ],
                'label' => 'Цена',
                'currency' => false,
                'auto_initialize' => false,
                'scale' => 0,
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

        $builder->add('date', HiddenType::class, [
            'required' => true,
        ]);

        $builder->get('date')
            ->addModelTransformer(
                new CallbackTransformer(
                    function(DateTimeImmutable|string|null $date) {
                        return (true === is_string($date)) ? new DateTimeImmutable($date) : $date;
                    },
                    function($date) {

                        return new DateTimeImmutable($date);
                    },
                ),
            );

        $builder->add('period', HiddenType::class, [
            'required' => true,
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


        /** События формы */

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if($data instanceof OrderServiceDTO)
                {

                    /**
                     * Date
                     */

                    $form->add('date', DateType::class, [
                        'widget' => 'single_text',
                        'label' => 'Дата',
                        'html5' => false,
                        'format' => 'dd.MM.yyyy',
                        'input' => 'datetime_immutable',
                        'required' => true,
                    ]);


                    if($data->getDate() instanceof DateTimeImmutable)
                    {
                        /**
                         * Period
                         */
                        $periods = $this->allServicePeriodByDateRepository
                            ->byDate($data->getDate())
                            ->findAll($data->getServ());

                        $periodResults = iterator_to_array($periods);

                        $periodChoice = array_map(function(AllServicePeriodByDateResult $period) {

                            $data = [
                                'time' => $period->getFrom()->format('H:i').' - '.$period->getTo()->format('H:i'),
                                'active' => $period->isOrderServiceActive()
                            ];

                            return new ServicePeriodUid($period->getPeriodId(), $data);
                        }, $periodResults);

                        $form->add('period', ChoiceType::class, [
                            'choices' => $periodChoice,
                            'choice_value' => function(?ServicePeriodUid $period) {

                                return $period?->getValue();
                            },
                            'choice_label' => function(ServicePeriodUid $period) use ($data): string {

                                if($data->getPeriod()->equals($period))
                                {
                                    return $period->getParams('time').($period->getParams('active') === true ? ' - ваша бронь' : '');
                                }

                                return $period->getParams('time').($period->getParams('active') === true ? ' - забронировано' : '');
                            },
                            'choice_attr' => function(ServicePeriodUid $period) use ($data): array {

                                if(true === $data->getPeriod()->equals($period))
                                {
                                    return ['class' => 'current'];
                                }

                                if(true === $period->getParams('active'))
                                {
                                    return ['class' => 'blocked'];
                                }

                                return [];
                            },
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
            'data_class' => OrderServiceDTO::class,
        ]);
    }
}