<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/ratelimit.php';
require_once __DIR__ . '/../../../../app/settings.php';

require_method('POST');
maintenance_guard();
rate_limit_guard('auth_ip_' . client_ip(), 5, 900);

$body = request_body();
$phone = preg_replace('/\D/', '', (string) ($body['phone'] ?? ''));

if (!preg_match('/^994\d{9}$/', $phone)) {
    json_error('PHONE_INCOMPLETE', 'Telefon nömrəsi natamamdır.', 422);
}

rate_limit_guard('auth_phone_' . $phone, 5, 900);

$stmt = db()->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
$stmt->execute(['phone' => $phone]);

json_ok(['exists' => $stmt->fetch() !== false]);
