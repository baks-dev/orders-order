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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Orders\Order\BaksDevOrdersOrderBundle;
use BaksDev\Orders\Order\Type\Delivery\Field\OrderDeliveryFieldType;
use BaksDev\Orders\Order\Type\Delivery\Field\OrderDeliveryFieldUid;
use BaksDev\Orders\Order\Type\Delivery\OrderDeliveryType;
use BaksDev\Orders\Order\Type\Delivery\OrderDeliveryUid;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Event\OrderEventUidType;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Id\OrderUidType;
use BaksDev\Orders\Order\Type\Payment\Field\OrderPaymentFieldType;
use BaksDev\Orders\Order\Type\Payment\Field\OrderPaymentFieldUid;
use BaksDev\Orders\Order\Type\Payment\OrderPaymentType;
use BaksDev\Orders\Order\Type\Payment\OrderPaymentUid;
use BaksDev\Orders\Order\Type\Product\OrderProductType;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatusType;
use BaksDev\Orders\Order\Type\User\OrderUserType;
use BaksDev\Orders\Order\Type\User\OrderUserUid;
use Symfony\Config\DoctrineConfig;

return static function (ContainerConfigurator $container, DoctrineConfig $doctrine) {

    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $doctrine->dbal()->type(OrderUid::TYPE)->class(OrderUidType::class);
    $services->set(OrderUid::class)->class(OrderUid::class); // #[ParamConverter(['order'])] OrderUid $order,


    $doctrine->dbal()->type(OrderEventUid::TYPE)->class(OrderEventUidType::class);
    $doctrine->dbal()->type(OrderProductUid::TYPE)->class(OrderProductType::class);

    $doctrine->dbal()->type(OrderUserUid::TYPE)->class(OrderUserType::class);

    $doctrine->dbal()->type(OrderPaymentUid::TYPE)->class(OrderPaymentType::class);
    $doctrine->dbal()->type(OrderPaymentFieldUid::TYPE)->class(OrderPaymentFieldType::class);


    $doctrine->dbal()->type(OrderDeliveryUid::TYPE)->class(OrderDeliveryType::class);
    $doctrine->dbal()->type(OrderDeliveryFieldUid::TYPE)->class(OrderDeliveryFieldType::class);

    $doctrine->dbal()->type(OrderStatus::TYPE)->class(OrderStatusType::class);

    $emDefault = $doctrine->orm()->entityManager('default')->autoMapping(true);


    $emDefault->mapping('orders-order')
        ->type('attribute')
        ->dir(BaksDevOrdersOrderBundle::PATH.'Entity')
        ->isBundle(false)
        ->prefix('BaksDev\Orders\Order\Entity')
        ->alias('orders-order');

};
