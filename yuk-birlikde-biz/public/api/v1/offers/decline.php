<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/offers.php';
require_once __DIR__ . '/../../../../app/orders.php';
require_once __DIR__ . '/../../../../app/notify.php';
require_once __DIR__ . '/../../../../app/sse_publish.php';

require_method('POST');
csrf_validate();
$user = require_auth();

$offerId = (int) ($_GET['id'] ?? 0);
$offer = fetch_offer_or_404($offerId);

if ((int) $offer['driver_id'] !== (int) $user['id']) {
    json_error('FORBIDDEN', text('forbidden'), 403);
}
if ($offer['status'] !== 'selected') {
    json_error('INVALID_STATUS', text('offer_decline_invalid_status'), 422);
}

$order = fetch_order_or_404((int) $offer['order_id']);

$db = db();
$db->beginTransaction();
try {
    $db->prepare(
        'UPDATE orders SET status = "active", selected_offer_id = NULL, reopen_count = reopen_count + 1 WHERE id = :id'
    )->execute(['id' => $order['id']]);

    $db->prepare('UPDATE offers SET status = "archived" WHERE id = :id')->execute(['id' => $offerId]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

notify_user((int) $order['customer_id'], 'driver_declined', 'Sürücü imtina etdi', text('notify_driver_declined'), "/elan/{$order['id']}");

$order['status'] = 'active';
publish_event('feed', 'listing.reopened', order_feed_payload($order));
publish_event("user:{$order['customer_id']}", 'selection.cancelled', ['order_id' => $order['id']]);

json_ok();
