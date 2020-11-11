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
