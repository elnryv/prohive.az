<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/offers.php';
require_once __DIR__ . '/../../../../app/orders.php';
require_once __DIR__ . '/../../../../app/sse_publish.php';

require_method('POST');
csrf_validate();
$user = require_auth();

$offerId = (int) ($_GET['id'] ?? 0);
$offer = fetch_offer_or_404($offerId);

if ((int) $offer['driver_id'] !== (int) $user['id']) {
    json_error('FORBIDDEN', text('forbidden'), 403);
}
if ($offer['status'] !== 'pending') {
    json_error('INVALID_STATUS', 'Yalnız gözləyən təklif geri çəkilə bilər.', 422);
}

$db = db();
$db->prepare('UPDATE offers SET status = "withdrawn" WHERE id = :id')->execute(['id' => $offerId]);

$remaining = order_offer_count((int) $offer['order_id']);
if ($remaining === 0) {
    $db->prepare('UPDATE orders SET status = "active" WHERE id = :id AND status = "waiting"')
        ->execute(['id' => $offer['order_id']]);
}

publish_event("listing:{$offer['order_id']}", 'offer.withdrawn', ['offer_id' => $offerId]);

json_ok();
