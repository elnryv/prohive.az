<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/subscription.php';
require_once __DIR__ . '/../../../../app/payriff.php';
require_once __DIR__ . '/../../../../app/notify.php';
require_once __DIR__ . '/../../../../app/sse_publish.php';
require_once __DIR__ . '/../../../../app/error_log.php';

require_method('POST');

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST;
}

$orderId = (string) ($body['orderId'] ?? $_GET['orderId'] ?? '');
if ($orderId === '') {
    json_error('ORDER_ID_MISSING', 'orderId tələb olunur.', 400);
}

$db = db();
$stmt = $db->prepare('SELECT * FROM payments WHERE payriff_tx_id = :tx ORDER BY id DESC LIMIT 1');
$stmt->execute(['tx' => $orderId]);
$payment = $stmt->fetch();

if ($payment === false) {
    json_error('PAYMENT_NOT_FOUND', 'Ödəniş tapılmadı.', 404);
}

// İdempotentlik: eyni tranzaksiya artıq uğurlu emal olunubsa təkrar emal edilmir.
if ($payment['status'] === 'success') {
    json_ok(['status' => 'already_processed']);
}

// Webhook bədəninə deyil, Payriff-in öz serverinə (gizli açarımızla) sorğu edib
// həqiqi statusu təsdiqləyirik — saxta callback sorğusu heç vaxt abunəni
// aktivləşdirə bilməz (Hissə 6.3 "imza yoxlanır" tələbi belə həyata keçirilir).
try {
    $orderInfo = payriff_get_order_info($orderId);
} catch (Throwable $e) {
    log_error('payriff_callback', $e->getMessage(), ['order_id' => $orderId]);
    json_error('PAYRIFF_STATUS_CHECK_FAILED', 'Status yoxlanıla bilmədi.', 502);
}

$status = payriff_status_from_payload($orderInfo);
$rawJson = json_encode($orderInfo, JSON_UNESCAPED_UNICODE);

if (!payriff_status_is_success($status)) {
    if (payriff_status_is_failed($status)) {
        $db->prepare("UPDATE payments SET status = 'failed', raw = :raw WHERE id = :id")
            ->execute(['raw' => $rawJson, 'id' => $payment['id']]);
    }
    json_ok(['status' => 'not_paid']);
}

$driverId = (int) $payment['driver_id'];
$days = settings_get_int('subscription_days', 30);

$db->beginTransaction();
try {
    $subscription = subscription_grant($driverId, 'payriff', $days);
    $db->prepare("UPDATE payments SET status = 'success', subscription_id = :sub_id, raw = :raw WHERE id = :id")
        ->execute([
            'sub_id' => $subscription['id'],
            'raw' => $rawJson,
            'id' => $payment['id'],
        ]);
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

notify_user($driverId, 'subscription_activated', 'Abunə aktivləşdi', 'Abunəniz aktivləşdi! İndi təklif göndərə bilərsiniz.', '/abune', 'system');
publish_event("user:{$driverId}", 'subscription.activated', ['ends_at' => $subscription['ends_at']]);

json_ok(['status' => 'success']);
