<?php

return [
    'driver' => 'smtp',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'from' => [
        'address' => env('INVITE_EMAIL_USERNAME'),
        'name' => 'Thai PBS e-Meeting'
    ],
    'encryption' => 'tls',
    'username' => env('INVITE_EMAIL_USERNAME'),
    'password' => env('INVITE_EMAIL_PASSWORD'),
    'sendmail' => '/usr/sbin/sendmail -bs',
    'markdown' => [
        'theme' => 'default',
        'paths' => [
            resource_path('views/vendor/mail')
        ]
    ]
];