<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order;

use BaksDev\Delivery\BaksDevDeliveryBundle;
use BaksDev\Delivery\Repository\DeliveryByProfileChoice\DeliveryByProfileChoiceRepository;
use BaksDev\Delivery\Repository\FieldByDeliveryChoice\FieldByDeliveryChoiceRepository;
use BaksDev\Payment\BaksDevPaymentBundle;
use BaksDev\Payment\Repository\FieldByPaymentChoice\FieldByPaymentChoiceRepository;
use BaksDev\Payment\Repository\PaymentByTypeProfileChoice\PaymentByTypeProfileChoiceRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class BaksDevOrdersOrderBundle extends AbstractBundle
{
    public const string NAMESPACE = __NAMESPACE__.'\\';

    public const string PATH = __DIR__.DIRECTORY_SEPARATOR;

    //    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    //    {
    //        $services = $container
    //            ->services()
    //            ->defaults()
    //            ->autowire()
    //            ->autoconfigure();
    //
    //        $services->load(self::NAMESPACE, self::PATH)
    //            ->exclude([
    //                self::PATH.'{Entity,Resources,Type}',
    //                self::PATH.'**/*Message.php',
    //                self::PATH.'**/*DTO.php',
    //            ]);
    //
    //        /* Статусы заказов */
    //        $services->load(
    //            self::NAMESPACE.'Type\Status\OrderStatus\\',
    //            self::PATH.'Type/Status/OrderStatus'
    //        );
    //
    //
    //        /** @see https://symfony.com/doc/current/service_container/autowiring.html#dealing-with-multiple-implementations-of-the-same-type */
    //
    //        $services->alias(OrderStatusInterface::class.' $orderStatusCanceled', OrderStatusCanceled::class);
    //        $services->alias(OrderStatusInterface::class.' $orderStatusCompleted', OrderStatusCompleted::class);
    //
    //        $services->alias(OrderStatusInterface::class.' $orderStatusExtradition', OrderStatusExtradition::class);
    //        $services->alias(OrderStatusInterface::class.' $orderStatusPackage', OrderStatusPackage::class);
    //        $services->alias(OrderStatusInterface::class.' $orderStatusNew', OrderStatusNew::class);
    //
    //
    //        $services->alias(OrderStatusInterface::class, OrderStatusNew::class);
    //
    //        if(class_exists(BaksDevDeliveryBundle::class))
    //        {
    //            $services->set(
    //                DeliveryByProfileChoiceInterface::class,
    //                DeliveryByProfileChoiceRepository::class
    //            );
    //
    //            $services->set(
    //                FieldByDeliveryChoiceInterface::class,
    //                FieldByDeliveryChoiceRepository::class
    //            );
    //        }
    //
    //        if(class_exists(BaksDevPaymentBundle::class))
    //        {
    //            $services->set(
    //                PaymentByTypeProfileChoiceInterface::class,
    //                PaymentByTypeProfileChoiceRepository::class
    //            );
    //
    //            $services->set(
    //                FieldByPaymentChoiceInterface::class,
    //                FieldByPaymentChoiceRepository::class
    //            );
    //        }
    //    }


    //    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    //    {
    //        $path = self::PATH.implode(DIRECTORY_SEPARATOR, ['Resources', 'config']);
    //
    //        $configs = new \RegexIterator(new \DirectoryIterator($path), '/\.php$/');
    //
    //        foreach($configs as $config)
    //        {
    //            if($config->isDot() || $config->isDir())
    //            {
    //                continue;
    //            }
    //            //if($config->isFile() && $config->getExtension() === 'php' && $config->getFilename() !== 'routes.php')
    //            //{
    //            $container->import($config->getPathname());
    //            //}
    //        }
    //    }

}
