<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/settings.php';

function session_create(int $userId): string
{
    $config = require __DIR__ . '/config.php';
    $days = settings_get_int('session_days', $config['session']['default_days']);

    $token = bin2hex(random_bytes(32));
    $stmt = db()->prepare(
        'INSERT INTO sessions (user_id, token_hash, device, ip, expires_at)
         VALUES (:user_id, :token_hash, :device, :ip, DATE_ADD(NOW(), INTERVAL :days DAY))'
    );
    $stmt->execute([
        'user_id' => $userId,
        'token_hash' => hash('sha256', $token),
        'device' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 120),
        'ip' => client_ip_safe(),
        'days' => $days,
    ]);

    setcookie($config['session']['cookie_name'], $token, [
        'expires' => time() + $days * 86400,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    return $token;
}

function session_user(): ?array
{
    $config = require __DIR__ . '/config.php';
    $token = $_COOKIE[$config['session']['cookie_name']] ?? '';
    if ($token === '') {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT u.* FROM sessions s
         JOIN users u ON u.id = s.user_id
         WHERE s.token_hash = :hash AND s.expires_at > NOW() AND u.status = "active"
         LIMIT 1'
    );
    $stmt->execute(['hash' => hash('sha256', $token)]);
    $user = $stmt->fetch();

    if ($user === false) {
        return null;
    }

    db()->prepare('UPDATE users SET last_seen_at = NOW() WHERE id = :id')
        ->execute(['id' => $user['id']]);

    return $user;
}

function require_auth(): array
{
    $user = session_user();
    if ($user === null) {
        json_error('UNAUTHENTICATED', 'Sessiya etibarsızdır, yenidən daxil olun.', 401);
    }
    return $user;
}

function app_session_destroy(): void
{
    $config = require __DIR__ . '/config.php';
    $name = $config['session']['cookie_name'];
    $token = $_COOKIE[$name] ?? '';

    if ($token !== '') {
        db()->prepare('DELETE FROM sessions WHERE token_hash = :hash')
            ->execute(['hash' => hash('sha256', $token)]);
    }

    setcookie($name, '', ['expires' => time() - 3600, 'path' => '/']);
}

function client_ip_safe(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// PIN attempt lockout (Hissə 3.5): 5 wrong attempts → 15 minute block, tracked
// per user id since it must survive across phone re-entry within that window.
function pin_attempts_blocked(int $userId): bool
{
    if (!function_exists('apcu_fetch')) {
        return false;
    }
    return (bool) apcu_fetch("pin_block_{$userId}");
}

function pin_attempts_register_failure(int $userId): void
{
    if (!function_exists('apcu_inc')) {
        return;
    }
    $key = "pin_fail_{$userId}";
    if (!apcu_exists($key)) {
        apcu_add($key, 0, 900);
    }
    $count = apcu_inc($key);
    if ($count >= 5) {
        apcu_store("pin_block_{$userId}", true, 900);
    }
}

function pin_attempts_reset(int $userId): void
{
    if (!function_exists('apcu_delete')) {
        return;
    }
    apcu_delete("pin_fail_{$userId}");
    apcu_delete("pin_block_{$userId}");
}
