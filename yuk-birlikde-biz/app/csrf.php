<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/response.php';

// Double-submit cookie: ybb_csrf is JS-readable (not httpOnly) so the SPA can
// echo it back in the X-CSRF-Token header; the session cookie stays httpOnly.
function csrf_ensure_token(): string
{
    $config = require __DIR__ . '/config.php';
    $name = $config['csrf']['cookie_name'];

    if (!empty($_COOKIE[$name])) {
        return $_COOKIE[$name];
    }

    $token = bin2hex(random_bytes(32));
    setcookie($name, $token, [
        'expires' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $token;

    return $token;
}

function csrf_validate(): void
{
    $config = require __DIR__ . '/config.php';
    $cookieName = $config['csrf']['cookie_name'];
    $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($config['csrf']['header_name']));

    $cookieToken = $_COOKIE[$cookieName] ?? '';
    $headerToken = $_SERVER[$headerName] ?? '';

    if ($cookieToken === '' || $headerToken === '' || !hash_equals($cookieToken, $headerToken)) {
        json_error('CSRF_INVALID', 'Sorğu doğrulanmadı, səhifəni yeniləyin.', 403);
    }
}
