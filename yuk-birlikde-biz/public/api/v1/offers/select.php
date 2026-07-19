<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/offers.php';
require_once __DIR__ . '/../../../../app/orders.php';
require_once __DIR__ . '/../../../../app/notify.php';

require_method('POST');
csrf_validate();
$user = require_auth();

$offerId = (int) ($_GET['id'] ?? 0);
$offer = fetch_offer_or_404($offerId);

$db = db();
$db->beginTransaction();
try {
    // Yarış şəraitinin qarşısını almaq üçün sətir kilidlənir (atomiklik, Hissə 4.6).
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
    $stmt->execute(['id' => $offer['order_id']]);
    $order = $stmt->fetch();

    if ($order === false || (int) $order['customer_id'] !== (int) $user['id']) {
        $db->rollBack();
        json_error('FORBIDDEN', text('forbidden'), 403);
    }
    if (!in_array($order['status'], ['active', 'waiting'], true) || $offer['status'] !== 'pending') {
        $db->rollBack();
        json_error('INVALID_STATUS', text('offer_select_invalid_status'), 409);
    }

    $db->prepare('UPDATE orders SET status = "negotiating", selected_offer_id = :offer_id WHERE id = :id')
        ->execute(['offer_id' => $offerId, 'id' => $order['id']]);

    $db->prepare('UPDATE offers SET status = "selected" WHERE id = :id')->execute(['id' => $offerId]);

    $db->prepare('UPDATE offers SET status = "archived" WHERE order_id = :order_id AND id != :offer_id AND status = "pending"')
        ->execute(['order_id' => $order['id'], 'offer_id' => $offerId]);

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $e;
}

$route = $order['from_city'] . ' → ' . $order['to_city'];
notify_user(
    (int) $offer['driver_id'],
    'selected',
    'Seçildiniz',
    sprintf(text('notify_selected'), $route),
    "/elan/{$order['id']}"
);

json_ok();
