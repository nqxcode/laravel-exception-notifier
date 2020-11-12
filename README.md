# Laravel exception notifier
Notify about exception in laravel project via telegram and email.
Notification attachment contains gzipped html with exception dump that rendered with [facade/ignition](https://github.com/facade/ignition).

![Screenshot_2020-11-12 🧨 Token could not be parsed from the request ](https://user-images.githubusercontent.com/5667387/98933663-02a53880-24f2-11eb-9701-56bcc3982354.png)

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

Change default config file  ```config/laravel-exception-notifier.php```, for example remove unnecessary channel: 

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
    'subject' => 'Исключение на сайте '.env('APP_URL'),
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

In ```.env``` add correct environment variables:
```ini
EXCEPTION_NOTIFIER_EMAIL=test@test.com
EXCEPTION_NOTIFIER_TELEGRAM_USER_ID=423460522
TELEGRAM_BOT_TOKEN=1160101879:AAFzuda0o7X6Dp4RBp00K-7dYjjnwMY887A

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
