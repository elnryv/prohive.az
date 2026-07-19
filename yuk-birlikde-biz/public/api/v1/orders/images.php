<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/image.php';
require_once __DIR__ . '/../../../../app/orders.php';

require_method('POST');
maintenance_guard();
csrf_validate();
$user = require_auth();

$orderId = (int) ($_GET['id'] ?? 0);
$order = fetch_order_or_404($orderId);

if ((int) $order['customer_id'] !== (int) $user['id']) {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

$existingCount = count(order_images_list($orderId));
$files = $_FILES['images'] ?? null;
if ($files === null) {
    json_error('FIELD_REQUIRED', 'Şəkil tapılmadı.', 422);
}

$count = is_array($files['name']) ? count($files['name']) : 1;
if ($existingCount + $count > 5) {
    json_error('IMAGES_LIMIT', text('images_limit'), 422);
}

$storageDir = __DIR__ . '/../../../../storage/uploads/orders';
$saved = [];

$db = db();
for ($i = 0; $i < $count; $i++) {
    $file = is_array($files['name']) ? [
        'name' => $files['name'][$i],
        'type' => $files['type'][$i],
        'tmp_name' => $files['tmp_name'][$i],
        'error' => $files['error'][$i],
        'size' => $files['size'][$i],
    ] : $files;

    try {
        $result = save_uploaded_order_image($file, $storageDir);
    } catch (RuntimeException $e) {
        continue;
    }

    $stmt = $db->prepare(
        'INSERT INTO order_images (order_id, path, thumb_path, sort) VALUES (:order_id, :path, :thumb_path, :sort)'
    );
    $stmt->execute([
        'order_id' => $orderId,
        'path' => 'orders/' . $result['path'],
        'thumb_path' => 'orders/' . $result['thumb_path'],
        'sort' => $existingCount + $i,
    ]);
    $saved[] = ['path' => 'orders/' . $result['path'], 'thumb_path' => 'orders/' . $result['thumb_path']];
}

json_ok(['images' => $saved]);
