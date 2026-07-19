<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/orders.php';
require_once __DIR__ . '/../../../../app/notify.php';

require_method('POST');
csrf_validate();
$user = require_auth();

$orderId = (int) ($_GET['id'] ?? 0);
$order = fetch_order_or_404($orderId);

if ((int) $order['customer_id'] !== (int) $user['id']) {
    json_error('FORBIDDEN', text('forbidden'), 403);
}
if ($order['status'] !== 'negotiating') {
    json_error('INVALID_STATUS', text('order_cancel_invalid_status'), 422);
}

$db = db();
$db->beginTransaction();
try {
    $db->prepare(
        'UPDATE orders SET status = "active", selected_offer_id = NULL, reopen_count = reopen_count + 1 WHERE id = :id'
    )->execute(['id' => $orderId]);

    $stmt = $db->prepare('SELECT driver_id FROM offers WHERE id = :id');
    $stmt->execute(['id' => $order['selected_offer_id']]);
    $driverId = $stmt->fetchColumn();

    $db->prepare('UPDATE offers SET status = "archived" WHERE id = :id')
        ->execute(['id' => $order['selected_offer_id']]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

if ($driverId !== false) {
    notify_user((int) $driverId, 'selection_cancelled', 'Seçim ləğv edildi', text('notify_selection_cancelled'), "/elan/{$orderId}");
}

json_ok();
