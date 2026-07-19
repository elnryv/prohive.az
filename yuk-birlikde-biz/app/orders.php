<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/texts.php';
require_once __DIR__ . '/notify.php';

function fetch_order_or_404(int $id): array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $order = $stmt->fetch();

    if ($order === false) {
        json_error('ORDER_NOT_FOUND', text('order_not_found'), 404);
    }

    return $order;
}

function order_images_list(int $orderId): array
{
    $stmt = db()->prepare('SELECT id, path, thumb_path, sort FROM order_images WHERE order_id = :id ORDER BY sort');
    $stmt->execute(['id' => $orderId]);
    return $stmt->fetchAll();
}

function order_offer_count(int $orderId): int
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM offers WHERE order_id = :id AND status IN ('pending', 'selected')"
    );
    $stmt->execute(['id' => $orderId]);
    return (int) $stmt->fetchColumn();
}

// SSE 'feed' kanalı üçün listing.new/listing.reopened payload-u (Hissə 7.3
// nümunəsinə uyğun) — order-ların yaradılması və yenidən açılması eyni formatı istifadə edir.
function order_feed_payload(array $order): array
{
    $db = db();
    $stmt = $db->prepare('SELECT name FROM cargo_types WHERE id = :id');
    $stmt->execute(['id' => $order['cargo_type_id']]);
    $cargoTypeName = $stmt->fetchColumn();

    $stmt = $db->prepare('SELECT COUNT(*) FROM order_images WHERE order_id = :id');
    $stmt->execute(['id' => $order['id']]);
    $hasImages = ((int) $stmt->fetchColumn()) > 0;

    return [
        'id' => (int) $order['id'],
        'number' => $order['number'],
        'cargo_type' => $cargoTypeName,
        'from' => $order['from_city'] . (!empty($order['from_district']) ? ', ' . $order['from_district'] : ''),
        'to' => $order['to_city'],
        'date_time' => $order['date_time'],
        'has_images' => $hasImages,
        'note_preview' => $order['note'] !== null ? mb_substr($order['note'], 0, 80) : '',
        // Client-side scope (Bakı daxili/Bölgələrarası) filtrinin real-time hadisələrə də tətbiqi üçün.
        'scope' => ($order['from_city'] === 'Bakı' && $order['to_city'] === 'Bakı') ? 'baku' : 'interregional',
    ];
}

// Marşrutu izləyən sürücülərə push göndərir — abunəsi olmayan sürücülərə də
// gedir (elana baxa bilər, təklifdə abunə soruşulur) — bax Hissə 5.8.
function notify_watching_drivers(array $order, string $cargoTypeName): void
{
    $stmt = db()->prepare(
        'SELECT DISTINCT driver_id FROM route_watches WHERE from_city = :from_city AND to_city = :to_city'
    );
    $stmt->execute(['from_city' => $order['from_city'], 'to_city' => $order['to_city']]);

    $fromLabel = $order['from_district'] ?: $order['from_city'];
    $toLabel = $order['to_district'] ?: $order['to_city'];
    $whenLabel = date('d.m H:i', strtotime($order['date_time']));

    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $driverId) {
        notify_user(
            (int) $driverId,
            'route_watch',
            'Yeni elan marşrutunuzda',
            sprintf(text('notify_route_watch'), $fromLabel, $toLabel, $whenLabel),
            "/elan/{$order['id']}"
        );
    }
}

function assign_order_number_and_slug(int $orderId): array
{
    $number = 'YK-' . date('Y') . '-' . str_pad((string) $orderId, 7, '0', STR_PAD_LEFT);
    $slug = bin2hex(random_bytes(12));

    db()->prepare('UPDATE orders SET number = :number, slug = :slug WHERE id = :id')
        ->execute(['number' => $number, 'slug' => $slug, 'id' => $orderId]);

    return [$number, $slug];
}
