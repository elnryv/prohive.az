<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/orders.php';

require_method('GET');
$user = require_auth();

if ($user['role'] !== 'customer') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

$allowedStatuses = ['active', 'waiting', 'negotiating', 'closed', 'cancelled', 'expired'];
// Hissə 4.10 "Aktiv" tabı active+waiting-i birləşdirir, ona görə vergüllə ayrılmış siyahı qəbul edilir.
$statuses = array_values(array_intersect($allowedStatuses, explode(',', (string) ($_GET['status'] ?? ''))));

$sql = 'SELECT o.*, c.name AS cargo_type_name,
        (SELECT COUNT(*) FROM offers WHERE order_id = o.id AND status IN ("pending", "selected")) AS offer_count,
        (SELECT thumb_path FROM order_images WHERE order_id = o.id ORDER BY sort LIMIT 1) AS thumb_path
        FROM orders o
        JOIN cargo_types c ON c.id = o.cargo_type_id
        WHERE o.customer_id = :customer_id';
$params = ['customer_id' => $user['id']];

if ($statuses !== []) {
    $placeholders = [];
    foreach ($statuses as $i => $value) {
        $key = "status{$i}";
        $placeholders[] = ":{$key}";
        $params[$key] = $value;
    }
    $sql .= ' AND o.status IN (' . implode(',', $placeholders) . ')';
}

$sql .= ' ORDER BY o.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

foreach ($orders as &$order) {
    $order['thumb_url'] = $order['thumb_path'] !== null ? '/uploads/' . $order['thumb_path'] : null;
    unset($order['thumb_path']);
}
unset($order);

json_ok(['orders' => $orders]);
