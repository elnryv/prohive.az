<?php
/**
 * Birlikdə Yük — konfiqurasiya nümunəsi.
 * Serverə köçürüləndə bu fayl `config.php` adı ilə kopyalanmalı və
 * dəyərlər doldurulmalıdır. `config.php` versiyalaşdırmaya düşməməlidir.
 */

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Birlikdə Yük',
        'env' => 'production', // 'production' | 'local'
        'base_url' => 'https://yuk.birlikde.biz',
        'admin_base_url' => 'https://yukadmin.birlikde.biz',
        'timezone' => 'Asia/Baku',
        'default_lang' => 'az',
        'supported_langs' => ['az', 'ru', 'en'],
    ],

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'yuk_db',
        'user' => 'yuk_user',
        'pass' => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],

    // HMAC üçün gizli açar (remember-cookie, CSRF token bağlaması). Serverdə
    // `openssl rand -hex 32` ilə yenisi yaradılmalıdır.
    'app_key' => 'CHANGE_ME_32_BYTE_RANDOM_HEX_STRING',

    'session' => [
        'name' => 'yuk_sess',
        'remember_cookie' => 'yuk_remember',
        'remember_days' => 30,
    ],

    'payriff' => [
        // Bölmə 8: Getdik-dəki PaymentGateway/PayriffProvider ilə eyni əməliyyat
        // qaydası. Açarlar boş olduqda ödəniş axını "tezliklə" mesajı ilə deaktivdir.
        'base_url' => 'https://api.payriff.com/api/v3',
        'merchant_id' => '',
        'secret_key' => '',
        'callback_url' => 'https://yuk.birlikde.biz/odenis/callback',
        'return_url' => 'https://yuk.birlikde.biz/surucu/odenis',
    ],

    // Web Push VAPID açarları — cron/vapid_keygen.php ilə generasiya olunur
    // və nəticə birbaşa `settings` cədvəlinə (vapid_public/vapid_private) yazılır.
    // Buradakı dəyərlər yalnız fallback üçündür, boş saxlanıla bilər.
    'vapid' => [
        'subject' => 'mailto:support@birlikde.biz',
    ],

    'upload' => [
        'max_photo_bytes' => 5 * 1024 * 1024,
        'vehicle_photo_dir' => __DIR__ . '/public/uploads/vehicles',
        'listing_photo_dir' => __DIR__ . '/public/uploads/listings',
        'profile_photo_dir' => __DIR__ . '/public/uploads/profiles',
        'og_image_dir' => __DIR__ . '/public/storage/og',
        'banner_dir' => __DIR__ . '/public/uploads/banners',
    ],
];
