<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/ratelimit.php';
require_once __DIR__ . '/../../../../app/texts.php';

require_method('POST');
maintenance_guard();
csrf_validate();
rate_limit_guard('auth_ip_' . client_ip(), 5, 900);

$body = request_body();
$phone = preg_replace('/\D/', '', (string) ($body['phone'] ?? ''));
$pin = (string) ($body['pin'] ?? '');

if (!preg_match('/^994\d{9}$/', $phone) || $pin === '') {
    json_error('INVALID_CREDENTIALS', text('invalid_credentials'), 422);
}

rate_limit_guard('auth_phone_' . $phone, 5, 900);

$stmt = db()->prepare('SELECT * FROM users WHERE phone = :phone LIMIT 1');
$stmt->execute(['phone' => $phone]);
$user = $stmt->fetch();

if ($user === false) {
    json_error('PHONE_NOT_FOUND', text('phone_not_found'), 404);
}

if ($user['status'] !== 'active') {
    json_error('ACCOUNT_BLOCKED', text('account_blocked'), 403);
}

if (pin_attempts_blocked((int) $user['id'])) {
    json_error('PIN_LOCKED', text('pin_locked'), 429);
}

if (!password_verify($pin, $user['pin_hash'])) {
    pin_attempts_register_failure((int) $user['id']);
    $locked = pin_attempts_blocked((int) $user['id']);
    json_error('PIN_WRONG', $locked ? text('pin_locked') : text('pin_wrong'), $locked ? 429 : 401);
}

pin_attempts_reset((int) $user['id']);
session_create((int) $user['id']);

json_ok(['user' => [
    'id' => (int) $user['id'],
    'role' => $user['role'],
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'avatar_path' => $user['avatar_path'],
]]);
