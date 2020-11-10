<?php

return [
    'routes' => [
        [
            'channel' => 'mail',
            'route' => env('EXCEPTION_NOTIFIER_MAIL', 'example@gmail.com'),
        ],
        [
            'channel' => 'telegram',
            'route' => env('EXCEPTION_NOTIFIER_TELEGRAM', '1234567890')
        ]
    ],
    'subject' => 'Исключение на сайте '.request()->root(),
];
