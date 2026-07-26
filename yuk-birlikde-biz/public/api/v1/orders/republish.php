<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/orders.php';

require_method('POST');
csrf_validate();
$user = require_auth();

$orderId = (int) ($_GET['id'] ?? 0);
$order = fetch_order_or_404($orderId);

if ((int) $order['customer_id'] !== (int) $user['id']) {
    json_error('FORBIDDEN', text('forbidden'), 403);
}
if ($order['status'] !== 'expired') {
    json_error('INVALID_STATUS', text('order_republish_invalid_status'), 422);
}

$ttlHours = settings_get_int('order_ttl_hours', 72);
$db = db();

$db->beginTransaction();
try {
    $stmt = $db->prepare(
        'INSERT INTO orders (
            number, customer_id, cargo_type_id,
            from_city, from_district, from_street, from_note,
            to_city, to_district, to_street, to_note,
            date_time, note, status, expires_at, slug
        ) VALUES (
            :number, :customer_id, :cargo_type_id,
            :from_city, :from_district, :from_street, :from_note,
            :to_city, :to_district, :to_street, :to_note,
            :date_time, :note, \'active\', DATE_ADD(NOW(), INTERVAL :ttl HOUR), :slug
        )'
    );
    $stmt->execute([
        'number' => 'pending-' . bin2hex(random_bytes(5)),
        'customer_id' => $user['id'],
        'cargo_type_id' => $order['cargo_type_id'],
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
        'ttl' => $ttlHours,
        'slug' => 'pending-' . bin2hex(random_bytes(6)),
    ]);

    $newId = (int) $db->lastInsertId();

    foreach (order_images_list($orderId) as $i => $image) {
        $db->prepare(
            'INSERT INTO order_images (order_id, path, thumb_path, sort) VALUES (:order_id, :path, :thumb_path, :sort)'
        )->execute([
            'order_id' => $newId,
            'path' => $image['path'],
            'thumb_path' => $image['thumb_path'],
            'sort' => $i,
        ]);
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

[$number, $slug] = assign_order_number_and_slug($newId);

json_ok(['order' => ['id' => $newId, 'number' => $number, 'slug' => $slug]], 201);
