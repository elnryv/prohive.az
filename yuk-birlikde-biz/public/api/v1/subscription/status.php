<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/texts.php';
require_once __DIR__ . '/../../../../app/subscription.php';

require_method('GET');
maintenance_guard();
$user = require_auth();

if ($user['role'] !== 'driver') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

$current = subscription_current((int) $user['id']);
$daysLeft = $current !== null ? (int) ceil((strtotime($current['ends_at']) - time()) / 86400) : 0;

$stmt = db()->prepare(
    'SELECT id, amount, currency, status, created_at FROM payments WHERE driver_id = :id ORDER BY created_at DESC LIMIT 20'
);
$stmt->execute(['id' => $user['id']]);

json_ok([
    'mode' => settings_get('subscription_mode', 'free'),
    'price' => (float) settings_get('subscription_price', '0'),
    'currency' => settings_get('subscription_currency', 'AZN'),
    'days' => settings_get_int('subscription_days', 30),
    'active' => $current !== null,
    'ends_at' => $current['ends_at'] ?? null,
    'days_left' => max(0, $daysLeft),
    'history' => $stmt->fetchAll(),
]);
