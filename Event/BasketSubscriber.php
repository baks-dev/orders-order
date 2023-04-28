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

declare(strict_types=1);

namespace BaksDev\Orders\Order\Event;

use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
// use App\Repository\ConferenceRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

final class BasketSubscriber implements EventSubscriberInterface
{
    private $twig;

    private TokenStorageInterface $storage;

    public function __construct(Environment $twig, TokenStorageInterface $storage)
    {
        $this->twig = $twig;
        $this->storage = $storage;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequestEvent', 1],
        ];
    }

    public function onRequestEvent(RequestEvent $event): void
    {
        $cache = new ApcuAdapter();

        $key = md5($event->getRequest()->getClientIp().$event->getRequest()->headers->get('USER-AGENT'));

        $counter = 0;

        // Получаем кеш
        if ($cache->hasItem($key)) {
            $products = $cache->getItem($key)->get();
            $counter = $products?->count();
        }

        $globals = $this->twig->getGlobals();
        $baks_basket = $globals['baks_basket'] ?? [];
        $this->twig->addGlobal('baks_basket', array_replace_recursive($baks_basket, ['counter' => $counter]));
    }
}
