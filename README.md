# BaksDev Order

[![Version](https://img.shields.io/badge/version-7.0.65-blue)](https://github.com/baks-dev/orders-order/releases)
![php 8.2+](https://img.shields.io/badge/php-min%208.1-red.svg)

Модуль системных заказов

## Установка

``` bash
$ composer require baks-dev/orders-order
```

## Дополнительно

Должен быть запущен воркер 'orders' для обработки асинхронных сообщений 

``` bash
$ php bin/console messenger:consume orders
```

Для добавления новых статусов необходимо создать сервис-класс, имплементирующий OrderStatusInterface c тегом 'baks.order.status'

``` php
<?php

namespace App\Orders\OrderStatus;

use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('baks.order.status')]
class OrderStatusDelivery implements OrderStatusInterface
{
... implements method
}
```

Установка файловых ресурсов в публичную директорию (javascript, css, image ...):

``` bash
$ php bin/console baks:assets:install
```

Изменения в схеме базы данных с помощью миграции

``` bash
$ php bin/console doctrine:migrations:diff

$ php bin/console doctrine:migrations:migrate
```
Тесты

``` bash
$ php bin/phpunit --group=orders-order
```

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.

