<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/settings.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/ratelimit.php';
require_once __DIR__ . '/../../../app/validator.php';
require_once __DIR__ . '/../../../app/texts.php';
require_once __DIR__ . '/../../../app/orders.php';

require_method('POST');
maintenance_guard();
csrf_validate();
$user = require_auth();

if ($user['role'] !== 'customer') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

rate_limit_guard('order_create_' . $user['id'], 10, 86400);

$body = request_body();
$missing = require_fields($body, ['cargo_type_id', 'date_time']);
if ($missing !== null) {
    json_error('FIELD_REQUIRED', "Sahə tələb olunur: {$missing}", 422);
}

$from = $body['from'] ?? [];
$to = $body['to'] ?? [];
if (empty($from['city']) || empty($to['city'])) {
    json_error('FIELD_REQUIRED', 'Şəhər sahəsi tələb olunur.', 422);
}

$dateTime = (string) $body['date_time'];
if (strtotime($dateTime) === false || strtotime($dateTime) < time()) {
    json_error('PAST_DATE', text('past_date'), 422);
}

$db = db();
$stmt = $db->prepare('SELECT id FROM cargo_types WHERE id = :id AND is_active = 1');
$stmt->execute(['id' => (int) $body['cargo_type_id']]);
if ($stmt->fetch() === false) {
    json_error('INVALID_CARGO_TYPE', 'Yük növü yanlışdır.', 422);
}

$ttlHours = settings_get_int('order_ttl_hours', 72);

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
    'cargo_type_id' => (int) $body['cargo_type_id'],
    'from_city' => trim((string) $from['city']),
    'from_district' => $from['district'] ?? null,
    'from_street' => $from['street'] ?? null,
    'from_note' => $from['note'] ?? null,
    'to_city' => trim((string) $to['city']),
    'to_district' => $to['district'] ?? null,
    'to_street' => $to['street'] ?? null,
    'to_note' => $to['note'] ?? null,
    'date_time' => date('Y-m-d H:i:s', strtotime($dateTime)),
    'note' => isset($body['note']) ? substr((string) $body['note'], 0, 500) : null,
    'ttl' => $ttlHours,
    'slug' => 'pending-' . bin2hex(random_bytes(6)),
]);

$orderId = (int) $db->lastInsertId();
[$number, $slug] = assign_order_number_and_slug($orderId);

json_ok(['order' => ['id' => $orderId, 'number' => $number, 'slug' => $slug]], 201);
