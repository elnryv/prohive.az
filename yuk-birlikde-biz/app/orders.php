<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/texts.php';

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

function assign_order_number_and_slug(int $orderId): array
{
    $number = 'YK-' . date('Y') . '-' . str_pad((string) $orderId, 7, '0', STR_PAD_LEFT);
    $slug = bin2hex(random_bytes(12));

    db()->prepare('UPDATE orders SET number = :number, slug = :slug WHERE id = :id')
        ->execute(['number' => $number, 'slug' => $slug, 'id' => $orderId]);

    return [$number, $slug];
}
