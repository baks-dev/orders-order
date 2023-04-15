# BaksDev Order

![Version](https://img.shields.io/badge/version-6.2-blue) ![php 8.1+](https://img.shields.io/badge/php-min%208.1-red.svg)

Модуль системных заказов

## Установка

``` bash
$ composer require baks-dev/orders-order
```

## Дополнительно

Должен быть запущен ворвкер 'orders' для обработки асинхронных сообщений 

``` bash
$ php bin/console messenger:consume orders
```

Установка файловых ресурсов в публичную директорию (javascript, css, image ...):

``` bash
$ php bin/console baks:assets:install
```

Роли администартора с помощью Fixtures

``` bash
$ php bin/console doctrine:fixtures:load --append
```

Изменения в схеме базы данных с помощью миграции

``` bash
$ php bin/console doctrine:migrations:diff

$ php bin/console doctrine:migrations:migrate
```

## Журнал изменений ![Changelog](https://img.shields.io/badge/changelog-yellow)

О том, что изменилось за последнее время, обратитесь к [CHANGELOG](CHANGELOG.md) за дополнительной информацией.

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.


