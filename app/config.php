<?php
declare(strict_types=1);

// ================== TƏTBIQ ==================
define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', 'Birlikdə Getdik');
define('APP_ENV', getenv('APP_ENV') ?: 'production'); // 'local'|'production'
define('APP_TIMEZONE', 'Asia/Baku');

define('SITE_BASE_URL', 'https://getdik.birlikde.biz');
define('ADMIN_BASE_URL', 'https://getdikadmin.birlikde.biz');

// ================== VERİLƏNLƏR BAZASI ==================
// Sahibkar özü doldurur (VPS-də real dəyərlər). Placeholder-lər lokal inkişaf üçündür.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'getdik');
define('DB_USER', getenv('DB_USER') ?: 'getdik');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ================== SESSİYA / TƏHLÜKƏSİZLİK ==================
define('SESSION_NAME', 'getdik_sess');
define('ADMIN_SESSION_NAME', 'getdik_admin_sess');
define('REMEMBER_COOKIE_NAME', 'getdik_remember');
define('REMEMBER_SECRET', getenv('REMEMBER_SECRET') ?: 'CHANGE_ME_IN_PRODUCTION');

// ================== PAYRIFF (v3) ==================
// Sahibkar özü doldurur. Boş qaldıqda ödəniş düymələri "tezliklə" rejimində.
define('PAYRIFF_SECRET_KEY', getenv('PAYRIFF_SECRET_KEY') ?: '');
define('PAYRIFF_BASE_URL', 'https://api.payriff.com');
define('PAYRIFF_CALLBACK', SITE_BASE_URL . '/odenis/callback');
define('PAYRIFF_RESULT_OK', SITE_BASE_URL . '/sahib/odenis?netice=ok');
define('PAYRIFF_RESULT_NO', SITE_BASE_URL . '/sahib/odenis?netice=xeta');

// ================== SABİTLƏR (Qərarlar Reyestri ilə uyğun default-lar) ==================
define('DEFAULT_LANG', 'az');
define('SUPPORTED_LANGS', ['az', 'ru', 'en']);
define('DEFAULT_MONTHLY_PRICE', '25.00');
define('TRIAL_DAYS', 30);
define('DEFAULT_CURRENCY', 'AZN');
