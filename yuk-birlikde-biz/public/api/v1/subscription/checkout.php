<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/ratelimit.php';
require_once __DIR__ . '/../../../../app/texts.php';
require_once __DIR__ . '/../../../../app/payriff.php';
require_once __DIR__ . '/../../../../app/error_log.php';

require_method('POST');
maintenance_guard();
csrf_validate();
$user = require_auth();

if ($user['role'] !== 'driver') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

rate_limit_guard('subscription_checkout_' . $user['id'], 10, 3600);

$price = (float) settings_get('subscription_price', '0');
$currency = settings_get('subscription_currency', 'AZN');

if ($price <= 0) {
    json_error('SUBSCRIPTION_PRICE_INVALID', 'Abunə qiyməti düzgün konfiqurasiya edilməyib.', 500);
}

$config = require __DIR__ . '/../../../../app/config.php';
$appUrl = rtrim($config['app_url'], '/');

$db = db();
$stmt = $db->prepare(
    'INSERT INTO payments (driver_id, amount, currency, status) VALUES (:driver_id, :amount, :currency, "pending")'
);
$stmt->execute(['driver_id' => $user['id'], 'amount' => $price, 'currency' => $currency]);
$paymentId = (int) $db->lastInsertId();

try {
    $order = payriff_create_order(
        $price,
        $currency,
        'Yük.Birlikdə.biz abunə',
        $appUrl . '/api/v1/subscription/payriff-callback',
        $appUrl . '/abune?payment=success',
        $appUrl . '/abune?payment=cancelled',
        $appUrl . '/abune?payment=declined'
    );
} catch (Throwable $e) {
    $db->prepare("UPDATE payments SET status = 'failed' WHERE id = :id")->execute(['id' => $paymentId]);
    log_error('payriff_checkout', $e->getMessage(), ['payment_id' => $paymentId]);
    json_error('PAYMENT_INIT_FAILED', 'Ödəniş başlada bilmədik. Bir az sonra yenidən cəhd edin.', 502);
}

$db->prepare('UPDATE payments SET payriff_tx_id = :tx WHERE id = :id')
    ->execute(['tx' => $order['order_id'], 'id' => $paymentId]);

json_ok(['payment_url' => $order['payment_url']]);
