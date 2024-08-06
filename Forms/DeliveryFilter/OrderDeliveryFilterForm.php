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

namespace BaksDev\Orders\Order\Forms\DeliveryFilter;

use BaksDev\Delivery\Forms\Delivery\DeliveryForm;
use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderDeliveryFilterForm extends AbstractType
{
    // время актуальности сессии (300 = 5 мин)
    private const int LIFETIME = 300;

    public function __construct(private readonly RequestStack $request) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('delivery', DeliveryForm::class, ['required' => false]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {

            /** @var OrderDeliveryFilterDTO $OrderDeliveryFilterDTO */
            $OrderDeliveryFilterDTO = $event->getData();

            if($session = $this->clearSessionLifetime())
            {
                $OrderDeliveryFilterDTO->setDelivery($session->get(OrderDeliveryFilterDTO::delivery));
            }
        });


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                /** @var OrderDeliveryFilterDTO $OrderDeliveryFilterDTO */
                $OrderDeliveryFilterDTO = $event->getData();

                $session = $this->request->getSession();

                if($OrderDeliveryFilterDTO->getDelivery() === null)
                {
                    $session->remove(OrderDeliveryFilterDTO::delivery);
                    return;
                }

                $this->request->getSession()->set(OrderDeliveryFilterDTO::delivery, $OrderDeliveryFilterDTO->getDelivery());

            }
        );

    }

    public function clearSessionLifetime(): ?SessionInterface
    {
        $session = $this->request->getSession();

        if(time() - $session->getMetadataBag()->getLastUsed() > self::LIFETIME)
        {
            $session->remove(OrderDeliveryFilterDTO::delivery);
            return null;
        }

        return $session;
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => OrderDeliveryFilterDTO::class,
                'method' => 'POST',
            ]
        );
    }
}
