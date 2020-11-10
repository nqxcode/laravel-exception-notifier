<?php

return [
    'routes' => [
        [
            'channel' => 'mail',
            'route' => 'example@gmail.com',
        ],
        [
            'channel' => 'telegram',
            'route' => '423460627'
        ]
    ],
    'subject' => 'Исключение на сайте '.request()->root(),
];
