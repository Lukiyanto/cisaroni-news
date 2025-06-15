<?php

return [
    'default' => env('FILAMENT_ADMIN_PANEL', 'admin'),

    'panels' => [
        'admin' => [
            'id' => 'admin',
            'path' => 'admin',
            'auth' => [
                'guard' => 'web',
                'pages' => [
                    'login' => \Filament\Pages\Auth\Login::class,
                    // 'request-password-reset' => \Filament\Pages\Auth\RequestPasswordReset::class,
                    // 'reset-password' => \Filament\Pages\Auth\ResetPassword::class,
                ],
            ],
            'database_notifications' => [
                'enabled' => true,
                'polling_interval' => '30s',
            ],
            'plugins' => [
                // Contoh plugin yang bisa digunakan
                // \Filament\Support\SupportServiceProvider::class,
            ],
            'middleware' => [
                'web',
                \Filament\Http\Middleware\Authenticate::class,
            ],
        ],
    ],

    'widgets' => [
        'account' => true,
        'info' => true,
    ],
];
