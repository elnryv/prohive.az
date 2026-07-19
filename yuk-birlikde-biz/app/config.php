<?php
declare(strict_types=1);

return [
    'env' => getenv('APP_ENV') ?: 'production',
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'yuk_birlikde',
        'user' => getenv('DB_USER') ?: 'yuk_birlikde',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'cookie_name' => 'ybb_session',
        'default_days' => 90,
    ],
    'csrf' => [
        'cookie_name' => 'ybb_csrf',
        'header_name' => 'X-CSRF-Token',
    ],
];
