<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/orders.php';

require_method('GET');
$user = require_auth();

if ($user['role'] !== 'driver') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

$orderId = (int) ($_GET['id'] ?? 0);
$order = fetch_order_or_404($orderId);

$db = db();
$stmt = $db->prepare('SELECT name FROM cargo_types WHERE id = :id');
$stmt->execute(['id' => $order['cargo_type_id']]);
$cargoTypeName = $stmt->fetchColumn();

$stmt = $db->prepare('SELECT first_name FROM users WHERE id = :id');
$stmt->execute(['id' => $order['customer_id']]);
$customerFirstName = $stmt->fetchColumn();

$stmt = $db->prepare('SELECT id, price, arrival_time, note, status FROM offers WHERE order_id = :order_id AND driver_id = :driver_id AND status != "withdrawn" LIMIT 1');
$stmt->execute(['order_id' => $orderId, 'driver_id' => $user['id']]);
$myOffer = $stmt->fetch() ?: null;

$phoneVisible = in_array($order['status'], ['negotiating', 'closed'], true)
    && $order['selected_offer_id'] !== null
    && $myOffer !== null
    && (int) $myOffer['id'] === (int) $order['selected_offer_id'];

$customerPhone = null;
if ($phoneVisible) {
    $stmt = $db->prepare('SELECT phone FROM users WHERE id = :id');
    $stmt->execute(['id' => $order['customer_id']]);
    $customerPhone = $stmt->fetchColumn();
}

json_ok([
    'order' => [
        'id' => (int) $order['id'],
        'number' => $order['number'],
        'cargo_type' => $cargoTypeName,
        'from_city' => $order['from_city'],
        'from_district' => $order['from_district'],
        'from_street' => $order['from_street'],
        'from_note' => $order['from_note'],
        'to_city' => $order['to_city'],
        'to_district' => $order['to_district'],
        'to_street' => $order['to_street'],
        'to_note' => $order['to_note'],
        'date_time' => $order['date_time'],
        'note' => $order['note'],
        'status' => $order['status'],
        'images' => order_images_list($orderId),
        'customer_first_name' => $customerFirstName,
        'customer_phone' => $customerPhone,
        'offer_count' => order_offer_count($orderId),
    ],
    'my_offer' => $myOffer,
]);
