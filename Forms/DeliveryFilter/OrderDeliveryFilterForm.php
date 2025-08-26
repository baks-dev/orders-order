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

namespace BaksDev\Orders\Order\Forms\DeliveryFilter;

use BaksDev\Delivery\Forms\Delivery\DeliveryForm;
use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
    private const int LIFETIME = 300;

    private string $sessionKey;

    public function __construct(private readonly RequestStack $request)
    {
        $this->sessionKey = md5(self::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('delivery', DeliveryForm::class, ['required' => false]);

        $builder->add('all', CheckboxType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event): void {

            $session = $this->request->getSession();
            if($session === false)
            {
                $session = $this->request->getSession();
            }

            if($session && $session->get('statusCode') === 307)
            {
                $session->remove($this->sessionKey);
                $session = false;
            }

            if($session && (time() - $session->getMetadataBag()->getLastUsed()) > self::LIFETIME)
            {
                $session->remove($this->sessionKey);
//                $session = false;
            }

            /** @var OrderDeliveryFilterDTO $OrderDeliveryFilterDTO */
            $OrderDeliveryFilterDTO = $event->getData();

            if($session = $this->clearSessionLifetime())
            {

                $sessionData = $this->request->getSession()->get($this->sessionKey);
                $sessionJson = $sessionData ? base64_decode($sessionData) : false;
                $sessionArray = $sessionJson !== false && json_validate($sessionJson) ? json_decode($sessionJson, true, 512, JSON_THROW_ON_ERROR) : false;

                if($sessionArray !== false)
                {
                    $session->remove($this->sessionKey);

                    !isset($sessionArray['all']) ?: $OrderDeliveryFilterDTO->setAll($sessionArray['all'] === true);
                    !isset($sessionArray['delivery']) ?: $OrderDeliveryFilterDTO->setDelivery(new CategoryProductUid($sessionArray['delivery']));
                }
            }

        });

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {
                /** @var OrderDeliveryFilterDTO $OrderDeliveryFilterDTO */
                $OrderDeliveryFilterDTO = $event->getData();

                $session = $this->request->getSession();

                if($session)
                {
                    if($OrderDeliveryFilterDTO->getDelivery() === null && $OrderDeliveryFilterDTO->getAll() === false)
                    {
                        $session->remove($this->sessionKey);
                        return;
                    }


                    $sessionArray = [];
                    $sessionArray['all'] = $OrderDeliveryFilterDTO->getAll();
                    $sessionArray['delivery'] = (string) $OrderDeliveryFilterDTO->getDelivery();


                    if($sessionArray)
                    {
                        $sessionJson = json_encode($sessionArray, JSON_THROW_ON_ERROR);
                        $sessionData = base64_encode($sessionJson);
                        $this->request->getSession()->set($this->sessionKey, $sessionData);
                        return;
                    }

                    $session->remove($this->sessionKey);
                }

            }
        );

    }

    public function clearSessionLifetime(): ?SessionInterface
    {
        $session = $this->request->getSession();

        if(time() - $session->getMetadataBag()->getLastUsed() > self::LIFETIME)
        {
            $session->remove($this->sessionKey);
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
