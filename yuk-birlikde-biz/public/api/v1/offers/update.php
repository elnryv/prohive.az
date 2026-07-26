<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/offers.php';

require_method('PATCH');
csrf_validate();
$user = require_auth();

$offerId = (int) ($_GET['id'] ?? 0);
$offer = fetch_offer_or_404($offerId);

if ((int) $offer['driver_id'] !== (int) $user['id']) {
    json_error('FORBIDDEN', text('forbidden'), 403);
}
if ($offer['status'] !== 'pending') {
    json_error('INVALID_STATUS', 'Yalnız gözləyən təklif dəyişdirilə bilər.', 422);
}

$body = request_body();
$fields = [];
$params = ['id' => $offerId];

if (isset($body['price'])) {
    $price = (float) $body['price'];
    if ($price <= 0 || $price > 99999) {
        json_error('PRICE_INVALID', 'Qiyməti daxil edin.', 422);
    }
    $fields[] = 'price = :price';
    $params['price'] = $price;
}
if (isset($body['arrival_time'])) {
    $fields[] = 'arrival_time = :arrival_time';
    $params['arrival_time'] = substr((string) $body['arrival_time'], 0, 50);
}
if (array_key_exists('note', $body)) {
    $fields[] = 'note = :note';
    $params['note'] = $body['note'] !== null ? substr((string) $body['note'], 0, 200) : null;
}

if ($fields === []) {
    json_ok();
}

$sql = 'UPDATE offers SET ' . implode(', ', $fields) . ' WHERE id = :id';
db()->prepare($sql)->execute($params);

json_ok();
