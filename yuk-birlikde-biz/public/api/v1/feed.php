<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/orders.php';

require_method('GET');
$user = require_auth();

if ($user['role'] !== 'driver') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

$sql = 'SELECT o.id, o.number, o.from_city, o.from_district, o.to_city, o.date_time, o.note,
        c.name AS cargo_type,
        (SELECT COUNT(*) FROM order_images WHERE order_id = o.id) AS image_count,
        (SELECT thumb_path FROM order_images WHERE order_id = o.id ORDER BY sort LIMIT 1) AS thumb_path
        FROM orders o
        JOIN cargo_types c ON c.id = o.cargo_type_id
        WHERE o.status IN ("active", "waiting")';
$params = [];

$scope = $_GET['scope'] ?? '';
if ($scope === 'baku') {
    $sql .= ' AND o.from_city = "Bakı" AND o.to_city = "Bakı"';
} elseif ($scope === 'interregional') {
    $sql .= ' AND (o.from_city != "Bakı" OR o.to_city != "Bakı")';
}

if (!empty($_GET['city'])) {
    $sql .= ' AND (o.from_city = :city OR o.to_city = :city)';
    $params['city'] = $_GET['city'];
}
if (!empty($_GET['district'])) {
    $sql .= ' AND o.from_district = :district';
    $params['district'] = $_GET['district'];
}
if (!empty($_GET['date'])) {
    $sql .= ' AND DATE(o.date_time) = :date';
    $params['date'] = $_GET['date'];
}
if (!empty($_GET['cargo_type'])) {
    $sql .= ' AND o.cargo_type_id = :cargo_type';
    $params['cargo_type'] = (int) $_GET['cargo_type'];
}

$sql .= ' ORDER BY o.created_at DESC LIMIT 50';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$listings = array_map(static function (array $row): array {
    return [
        'id' => (int) $row['id'],
        'number' => $row['number'],
        'cargo_type' => $row['cargo_type'],
        'from' => $row['from_city'] . ($row['from_district'] ? ", {$row['from_district']}" : ''),
        'to' => $row['to_city'],
        'date_time' => $row['date_time'],
        'has_images' => ((int) $row['image_count']) > 0,
        'thumb_url' => $row['thumb_path'] !== null ? '/uploads/' . $row['thumb_path'] : null,
        'note_preview' => $row['note'] !== null ? mb_substr($row['note'], 0, 80) : '',
    ];
}, $rows);

json_ok(['listings' => $listings]);
