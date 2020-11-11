# Laravel exception notifier
Notify about exception in laravel project via telegram and email.

## Installation

Require this package in your composer.json and run composer update:

```bash
composer require "nqxcode/laravel-exception-notifier"
```

## Configuration

Publish the config file into your project by running:

```bash
php artisan vendor:publish --provider="Nqxcode\LaravelExceptionNotifier\ServiceProvider"
```

Change default config file  ```config/laravel-exception-notifier.php``` if it is needed: 

```php
<?php

return [
    'routes' => [
        [
            'channel' => 'mail',
            'route' => env('EXCEPTION_NOTIFIER_EMAIL', 'example@gmail.com'),
        ],
        [
            'channel' => 'telegram',
            'route' => env('EXCEPTION_NOTIFIER_TELEGRAM_USER_ID', '1234567890')
        ]
    ],
    'subject' => 'Исключение на сайте '.request()->root(),
];

```


Add to  ```config/services.php``` following: 
```php
<?php
return [
    
    // ...

    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
    ]
];
```


To notify about exception for ```production``` environment in file ```app/Exceptions/Handler.php``` modify ```report``` method:

```php
<?php
namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Nqxcode\LaravelExceptionNotifier\ExceptionNotifierInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    // ...

    public function report(Throwable $exception)
    {
        if ($this->container->isProduction() && $this->shouldReport($exception)) {
            $this->container->make(ExceptionNotifierInterface::class)->notify($exception);
        }

        parent::report($exception);
    }
    
    // ...
}
```

##
## License
Package licenced under the MIT license.