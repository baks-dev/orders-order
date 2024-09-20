# BaksDev Order

[![Version](https://img.shields.io/badge/version-7.1.51-blue)](https://github.com/baks-dev/orders-order/releases)
![php 8.3+](https://img.shields.io/badge/php-min%208.3-red.svg)

Модуль системных заказов

## Установка

``` bash
composer require \
baks-dev/payment \
baks-dev/users-address \
baks-dev/contacts-region \
baks-dev/centrifugo \
baks-dev/orders-order
```

## Cервер Centrifugo

Установка и настройка [baks-dev/centrifugo](https://github.com/baks-dev/centrifugo)

## Дополнительно

Установка конфигурации и файловых ресурсов:

``` bash
php bin/console baks:assets:install
```

Для обработки сообщений асинхронно должен быть запущен воркер 'orders-order'

``` bash
php bin/console messenger:consume orders-order
```

Для добавления новых статусов необходимо создать сервис-класс, имплементирующий OrderStatusInterface c тегом '
baks.order.status'

``` php
<?php

namespace App\Orders\OrderStatus;

use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('baks.order.status')]
class OrderStatusCustom implements OrderStatusInterface
{
... implements method
}
```

Изменения в схеме базы данных с помощью миграции

``` bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Тестирование

``` bash
php bin/phpunit --group=orders-order
```

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.
