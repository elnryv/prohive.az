<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/settings.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/ratelimit.php';
require_once __DIR__ . '/../../../app/validator.php';
require_once __DIR__ . '/../../../app/texts.php';
require_once __DIR__ . '/../../../app/orders.php';
require_once __DIR__ . '/../../../app/notify.php';

require_method('POST');
maintenance_guard();
csrf_validate();
$user = require_auth();

if ($user['role'] !== 'driver') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

rate_limit_guard('offer_create_' . $user['id'], 30, 3600);

$body = request_body();
$missing = require_fields($body, ['order_id', 'price', 'arrival_time']);
if ($missing !== null) {
    json_error('FIELD_REQUIRED', "Sahə tələb olunur: {$missing}", 422);
}

$price = (float) $body['price'];
if ($price <= 0 || $price > 99999) {
    json_error('PRICE_INVALID', 'Qiyməti daxil edin.', 422);
}

$orderId = (int) $body['order_id'];
$order = fetch_order_or_404($orderId);

if (!in_array($order['status'], ['active', 'waiting'], true)) {
    json_error('ORDER_NOT_ACTIVE', text('order_not_active'), 403);
}

$db = db();
$stmt = $db->prepare(
    'SELECT id FROM offers WHERE order_id = :order_id AND driver_id = :driver_id AND status = "pending" LIMIT 1'
);
$stmt->execute(['order_id' => $orderId, 'driver_id' => $user['id']]);
if ($stmt->fetch() !== false) {
    json_error('OFFER_DUPLICATE', text('offer_duplicate'), 409);
}

$db->beginTransaction();
try {
    $stmt = $db->prepare(
        'INSERT INTO offers (order_id, driver_id, price, arrival_time, note, status)
         VALUES (:order_id, :driver_id, :price, :arrival_time, :note, "pending")'
    );
    $stmt->execute([
        'order_id' => $orderId,
        'driver_id' => $user['id'],
        'price' => $price,
        'arrival_time' => substr((string) $body['arrival_time'], 0, 50),
        'note' => isset($body['note']) ? substr((string) $body['note'], 0, 200) : null,
    ]);
    $offerId = (int) $db->lastInsertId();

    if ($order['status'] === 'active') {
        $db->prepare('UPDATE orders SET status = "waiting" WHERE id = :id')->execute(['id' => $orderId]);
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

$route = $order['from_city'] . ' → ' . $order['to_city'];
$driverName = trim($user['first_name'] . ' ' . $user['last_name']);
notify_user(
    (int) $order['customer_id'],
    'new_offer',
    'Yeni təklif',
    sprintf(text('notify_new_offer'), $driverName, number_format($price, 2), $route),
    "/elan/{$orderId}",
    'offers'
);

json_ok(['offer' => ['id' => $offerId]], 201);
