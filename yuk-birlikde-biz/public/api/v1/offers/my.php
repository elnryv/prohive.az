<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';

require_method('GET');
$user = require_auth();

if ($user['role'] !== 'driver') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

$tab = $_GET['tab'] ?? 'active';
$status = $tab === 'selected' ? 'selected' : 'pending';

$stmt = db()->prepare(
    'SELECT of.id, of.price, of.arrival_time, of.note, of.status, of.created_at,
            o.id AS order_id, o.number, o.from_city, o.to_city, o.date_time, o.status AS order_status,
            u.first_name AS customer_first_name, u.last_name AS customer_last_name,
            u.phone AS customer_phone
     FROM offers of
     JOIN orders o ON o.id = of.order_id
     JOIN users u ON u.id = o.customer_id
     WHERE of.driver_id = :driver_id AND of.status = :status
     ORDER BY of.created_at DESC'
);
$stmt->execute(['driver_id' => $user['id'], 'status' => $status]);
$rows = $stmt->fetchAll();

foreach ($rows as &$row) {
    $row['customer_name'] = trim($row['customer_first_name'] . ' ' . $row['customer_last_name']);
    unset($row['customer_first_name'], $row['customer_last_name']);
    if ($tab !== 'selected') {
        unset($row['customer_phone']);
    }
}
unset($row);

json_ok(['offers' => $rows]);
