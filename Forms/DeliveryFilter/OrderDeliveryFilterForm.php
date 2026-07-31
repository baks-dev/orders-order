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
use ReflectionClass;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderDeliveryFilterForm extends AbstractType
{
    private const int LIFETIME = 300;

    private SessionInterface|false $session = false;

    private string $sessionKey;

    public function __construct(private readonly RequestStack $request)
    {
        $this->sessionKey = '390e1c2a-4688-7dde-8e73-885ab3470a47';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('product', TextType::class, [
            'required' => false,
            'attr' => ['class' => 'small'],
        ]);

        $builder->add('client', TextType::class, [
            'required' => false,
            'attr' => ['class' => 'small'],
        ]);

        $builder->add('delivery', DeliveryForm::class, [
            'required' => false,
        ]);

        $builder->add('all', CheckboxType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event): void {

            /** @var OrderDeliveryFilterDTO $data */
            $data = $event->getData();

            $article = $this->request->getMainRequest()->query->get('article', null);

            if($article)
            {
                $data->setProduct($article);
                return;
            }

            if($this->session === false)
            {
                try
                {
                    $this->session = $this->request->getSession();
                }
                catch(SessionNotFoundException)
                {
                    return;
                }
            }

            if($this->session && $this->session->get('statusCode') === 307)
            {
                $this->session->remove($this->sessionKey);
                $this->session = false;
            }

            if($this->session && (time() - $this->session->getMetadataBag()->getLastUsed()) > 300)
            {
                $this->session->remove($this->sessionKey);
                $this->session = false;
            }

            if(false === $this->session instanceof SessionInterface)
            {
                return;
            }

            if(time() - $this->session->getMetadataBag()->getLastUsed() > self::LIFETIME)
            {
                $this->session->remove($this->sessionKey);
                return;
            }

            $sessionData = $this->request->getSession()->get($this->sessionKey);
            $sessionJson = $sessionData ? base64_decode($sessionData) : false;
            $sessionArray = $sessionJson !== false && json_validate($sessionJson) ? json_decode($sessionJson, true, 512, JSON_THROW_ON_ERROR) : false;

            if(empty($sessionArray))
            {
                return;
            }

            foreach($sessionArray as $key => $value)
            {
                // Устанавливаем null через сеттер
                if(method_exists($data, 'set'.ucfirst($key)))
                {
                    $data->{'set'.ucfirst($key)}($value);
                }
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event): void {

            if($this->session === false)
            {
                try
                {
                    $this->session = $this->request->getSession();
                }
                catch(SessionNotFoundException)
                {
                    return;
                }
            }

            /** @var OrderDeliveryFilterDTO $data */
            $data = $event->getData();
            $this->session->remove($this->sessionKey);
            $sessionArray = [];

            $reflection = new ReflectionClass(OrderDeliveryFilterDTO::class);

            foreach($reflection->getProperties() as $property)
            {
                $name = $property->getName();
                $getter = 'get'.ucfirst($name);

                if(method_exists($data, $getter))
                {
                    $value = (string) $data->{$getter}();

                    if(empty($value))
                    {
                        continue;
                    }

                    $sessionArray[$name] = $value;
                }
            }

            if($sessionArray)
            {
                $sessionJson = json_encode($sessionArray, JSON_THROW_ON_ERROR);
                $sessionData = base64_encode($sessionJson);
                $this->request->getSession()->set($this->sessionKey, $sessionData);

                return;
            }

            $this->session->remove($this->sessionKey);
        });

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
            ],
        );
    }
}
