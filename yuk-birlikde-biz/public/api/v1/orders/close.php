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
    json_error('INVALID_STATUS', text('order_close_invalid_status'), 422);
}

$db = db();
$db->prepare('UPDATE orders SET status = "closed" WHERE id = :id')->execute(['id' => $orderId]);

$selectedOffer = $db->prepare('SELECT driver_id FROM offers WHERE id = :id');
$selectedOffer->execute(['id' => $order['selected_offer_id']]);
$driverId = $selectedOffer->fetchColumn();

if ($driverId !== false) {
    notify_user((int) $driverId, 'order_closed', 'Sifariş bağlandı', text('notify_order_closed'), "/elan/{$orderId}");
}

json_ok();
