<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/validator.php';
require_once __DIR__ . '/../../../app/texts.php';
require_once __DIR__ . '/../../../app/orders.php';

require_method('POST');
csrf_validate();
$user = require_auth();

if ($user['role'] !== 'customer') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

$body = request_body();
$missing = require_fields($body, ['order_id', 'stars']);
if ($missing !== null) {
    json_error('FIELD_REQUIRED', "Sahə tələb olunur: {$missing}", 422);
}

$stars = (int) $body['stars'];
if ($stars < 1 || $stars > 5) {
    json_error('STARS_INVALID', 'Qiymətləndirmə 1-5 arasında olmalıdır.', 422);
}

$orderId = (int) $body['order_id'];
$order = fetch_order_or_404($orderId);

if ((int) $order['customer_id'] !== (int) $user['id']) {
    json_error('FORBIDDEN', text('forbidden'), 403);
}
if ($order['status'] !== 'closed') {
    json_error('INVALID_STATUS', text('rating_invalid_status'), 422);
}

$db = db();
$stmt = $db->prepare('SELECT id FROM ratings WHERE order_id = :order_id');
$stmt->execute(['order_id' => $orderId]);
if ($stmt->fetch() !== false) {
    json_error('RATING_EXISTS', text('rating_exists'), 409);
}

$stmt = $db->prepare('SELECT driver_id FROM offers WHERE id = :id');
$stmt->execute(['id' => $order['selected_offer_id']]);
$driverId = $stmt->fetchColumn();

if ($driverId === false) {
    json_error('INVALID_STATUS', text('rating_invalid_status'), 422);
}

$note = isset($body['note']) ? substr((string) $body['note'], 0, 200) : null;

$db->beginTransaction();
try {
    $db->prepare(
        'INSERT INTO ratings (order_id, customer_id, driver_id, stars, note) VALUES (:order_id, :customer_id, :driver_id, :stars, :note)'
    )->execute([
        'order_id' => $orderId,
        'customer_id' => $user['id'],
        'driver_id' => $driverId,
        'stars' => $stars,
        'note' => $note,
    ]);

    // Sıra vacibdir: rating_avg köhnə rating_count-a görə hesablanmalıdır,
    // ona görə count artırılmazdan əvvəl gəlir (MySQL SET-ləri soldan sağa qiymətləndirir).
    $db->prepare(
        'UPDATE drivers SET
            rating_avg = (COALESCE(rating_avg, 0) * rating_count + :stars) / (rating_count + 1),
            rating_count = rating_count + 1
         WHERE user_id = :driver_id'
    )->execute(['stars' => $stars, 'driver_id' => $driverId]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

json_ok([], 201);
