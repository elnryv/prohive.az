<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/orders.php';

require_method('GET');
$user = require_auth();

$orderId = (int) ($_GET['id'] ?? 0);
$order = fetch_order_or_404($orderId);

if ((int) $order['customer_id'] !== (int) $user['id']) {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

$db = db();

$stmt = $db->prepare('SELECT name FROM cargo_types WHERE id = :id');
$stmt->execute(['id' => $order['cargo_type_id']]);
$order['cargo_type_name'] = $stmt->fetchColumn();
$order['images'] = order_images_list($orderId);

$phoneVisible = in_array($order['status'], ['negotiating', 'closed'], true);

$stmt = $db->prepare(
    'SELECT o.id, o.driver_id, o.price, o.arrival_time, o.note, o.status, o.created_at,
            u.first_name, u.last_name, u.avatar_path,
            v.name AS vehicle_name, vs.code AS vehicle_size_code,
            d.rating_avg, d.rating_count,
            u.phone AS driver_phone
     FROM offers o
     JOIN drivers d ON d.user_id = o.driver_id
     JOIN users u ON u.id = o.driver_id
     JOIN vehicles v ON v.id = d.vehicle_id
     JOIN vehicle_sizes vs ON vs.id = d.vehicle_size_id
     WHERE o.order_id = :order_id AND o.status != "withdrawn"
     ORDER BY o.created_at DESC'
);
$stmt->execute(['order_id' => $orderId]);
$offers = $stmt->fetchAll();

foreach ($offers as &$offer) {
    $offer['driver_name'] = trim($offer['first_name'] . ' ' . $offer['last_name']);
    unset($offer['first_name'], $offer['last_name']);
    if (!($phoneVisible && $offer['status'] === 'selected')) {
        unset($offer['driver_phone']);
    }
}
unset($offer);

json_ok(['order' => $order, 'offers' => $offers]);
