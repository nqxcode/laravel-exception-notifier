<?php

return [
    'alert_mail' => [
        'address' => env('MAIL_ALERT_ADDRESS', [
            'nc101ux@gmail.com',
        ]),
        'subject' => 'Исключение на сайте ' . request()->root(),
        'sending_interval' => 60,
    ],
    'dump_file_name' => 'exception-dump',
];
