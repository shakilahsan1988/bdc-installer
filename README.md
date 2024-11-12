# bdCoder Web Installer

- [About](#about)
- [Requirements](#requirements)
- [Installation](#installation)

## About

Using this package you can easily install your laravel project automatically in your server.

## Requirements

* [Laravel 8.2+](https://laravel.com/docs/installation)

## Installation

1. From your projects root folder in terminal run:

```bash
    composer composer require bdcoder/installer
```

2. Register the package

Register the package with laravel in `bootstrap` under `providers.php` with the following:

```php
return [
    ...
    Bdc\Installer\Providers\AppServiceProvider::class,
];

```

3. Publish the packages views, config file, assets, and language files by running the following from your projects root folder:

```bash
    php artisan vendor:publish --provider="Bdc\Installer\Providers\AppServiceProvider"
```
