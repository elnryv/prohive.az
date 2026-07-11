<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => Env::getBool('APP_DEBUG', false),
        'url' => Env::get('APP_URL', 'https://app.birlikde.biz'),
        'admin_url' => Env::get('ADMIN_URL', 'https://appadmin.birlikde.biz'),
        'timezone' => Env::get('APP_TIMEZONE', 'Asia/Baku'),
    ],
    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', ''),
        'username' => Env::get('DB_USERNAME', ''),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    ],
    'session' => [
        'name' => Env::get('SESSION_NAME', 'birlikde_session'),
        'lifetime' => Env::getInt('SESSION_LIFETIME', 0),
        'secure' => Env::getBool('SESSION_SECURE', true),
        'samesite' => Env::get('SESSION_SAMESITE', 'Lax'),
    ],
    'csrf' => [
        'token_name' => Env::get('CSRF_TOKEN_NAME', 'csrf_token'),
    ],
    'remember_me' => [
        'cookie' => Env::get('REMEMBER_ME_COOKIE', 'birlikde_remember'),
        'days' => Env::getInt('REMEMBER_ME_DAYS', 365),
    ],
    'admin' => [
        'session_name' => Env::get('ADMIN_SESSION_NAME', 'birlikde_admin_session'),
        'idle_minutes' => Env::getInt('ADMIN_SESSION_IDLE_MINUTES', 15),
    ],
    'payment' => [
        'provider' => Env::get('PAYMENT_PROVIDER', 'birbank'),
    ],
    'push' => [
        'vapid_public_key' => Env::get('VAPID_PUBLIC_KEY', ''),
        'vapid_private_key' => Env::get('VAPID_PRIVATE_KEY', ''),
        'vapid_subject' => Env::get('VAPID_SUBJECT', ''),
    ],
    'whatsapp' => [
        'support_number' => Env::get('WHATSAPP_SUPPORT_NUMBER', ''),
    ],
];
