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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Delivery\BaksDevDeliveryBundle;
use BaksDev\Delivery\Repository\DeliveryByProfileChoice\DeliveryByProfileChoiceRepository;
use BaksDev\Delivery\Repository\FieldByDeliveryChoice\FieldByDeliveryChoiceRepository;
use BaksDev\Orders\Order\BaksDevOrdersOrderBundle;
use BaksDev\Orders\Order\Repository\DeliveryByProfileChoice\DeliveryByProfileChoiceInterface;
use BaksDev\Orders\Order\Repository\FieldByDeliveryChoice\FieldByDeliveryChoiceInterface;
use BaksDev\Orders\Order\Repository\FieldByPaymentChoice\FieldByPaymentChoiceInterface;
use BaksDev\Orders\Order\Repository\PaymentByTypeProfileChoice\PaymentByTypeProfileChoiceInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCompleted;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusExtradition;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusPackage;
use BaksDev\Payment\BaksDevPaymentBundle;
use BaksDev\Payment\Repository\FieldByPaymentChoice\FieldByPaymentChoiceRepository;
use BaksDev\Payment\Repository\PaymentByTypeProfileChoice\PaymentByTypeProfileChoiceRepository;

return static function(ContainerConfigurator $container) {

    $services = $container->services()
        ->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $NAMESPACE = BaksDevOrdersOrderBundle::NAMESPACE;
    $PATH = BaksDevOrdersOrderBundle::PATH;


    $services->load($NAMESPACE, $PATH)
        ->exclude([
            $PATH.'{Entity,Resources,Type}',
            $PATH.'**'.DIRECTORY_SEPARATOR.'*Message.php',
            $PATH.'**'.DIRECTORY_SEPARATOR.'*Result.php',
            $PATH.'**'.DIRECTORY_SEPARATOR.'*DTO.php',
        ]);


    /* Статусы заказов */
    $services->load(
        $NAMESPACE.'Type\Status\OrderStatus\\',
        $PATH.implode(DIRECTORY_SEPARATOR, ['Type', 'Status', 'OrderStatus'])
    );


    /** @see https://symfony.com/doc/current/service_container/autowiring.html#dealing-with-multiple-implementations-of-the-same-type */

    $services->alias(OrderStatusInterface::class.' $orderStatusCanceled', OrderStatusCanceled::class);
    $services->alias(OrderStatusInterface::class.' $orderStatusCompleted', OrderStatusCompleted::class);

    $services->alias(OrderStatusInterface::class.' $orderStatusExtradition', OrderStatusExtradition::class);
    $services->alias(OrderStatusInterface::class.' $orderStatusPackage', OrderStatusPackage::class);
    $services->alias(OrderStatusInterface::class.' $orderStatusNew', OrderStatusNew::class);


    $services->alias(OrderStatusInterface::class, OrderStatusNew::class);

    if(class_exists(BaksDevDeliveryBundle::class))
    {
        $services->set(
            DeliveryByProfileChoiceInterface::class,
            DeliveryByProfileChoiceRepository::class
        );

        $services->set(
            FieldByDeliveryChoiceInterface::class,
            FieldByDeliveryChoiceRepository::class
        );
    }

    if(class_exists(BaksDevPaymentBundle::class))
    {
        $services->set(
            PaymentByTypeProfileChoiceInterface::class,
            PaymentByTypeProfileChoiceRepository::class
        );

        $services->set(
            FieldByPaymentChoiceInterface::class,
            FieldByPaymentChoiceRepository::class
        );
    }

};
