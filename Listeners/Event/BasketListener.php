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

namespace BaksDev\Orders\Order\Listeners\Event;

use BaksDev\Core\Cache\AppCacheInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

#[AsEventListener(event: RequestEvent::class, priority: 1)]
final class BasketListener
{
    public function __construct(
        private readonly Environment $twig,
        private readonly AppCacheInterface $cache
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $AppCache = $this->cache->init('orders-order-basket');

        $key = md5($event->getRequest()->getClientIp().$event->getRequest()->headers->get('USER-AGENT'));

        $counter = 0;

        // Получаем кеш
        if($AppCache->hasItem($key))
        {
            $products = ($AppCache->getItem($key))->get();
            $counter = $products?->count();
        }

        $globals = $this->twig->getGlobals();
        $baks_basket = $globals['baks_basket'] ?? [];
        $this->twig->addGlobal('baks_basket', array_replace_recursive($baks_basket, ['counter' => $counter]));
    }
}
