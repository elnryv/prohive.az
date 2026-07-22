<?php
declare(strict_types=1);

// GET /og/{slug}.png — Hissə 12 fayl strukturu: "OG kart keşi". Bir dəfə
// generasiya olunur, storage/og/{slug}.png-də saxlanılır, sonrakı sorğular
// birbaşa fayldan xidmət olunur (nginx-də try_files ilə PHP-yə belə çatmır).

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/og_generator.php';

$slug = preg_replace('/[^a-f0-9]/', '', (string) ($_GET['slug'] ?? ''));
$cacheDir = __DIR__ . '/../../storage/og';
$cachePath = "{$cacheDir}/{$slug}.png";

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

if ($slug === '' || !is_file($cachePath)) {
    if ($slug !== '') {
        $stmt = db()->prepare(
            'SELECT o.*, ct.name AS cargo_type_name FROM orders o
             JOIN cargo_types ct ON ct.id = o.cargo_type_id
             WHERE o.slug = :slug LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $order = $stmt->fetch();

        if ($order !== false) {
            generate_og_image($order, $order['cargo_type_name'], $cachePath);
        }
    }
}

if (!is_file($cachePath)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Not found';
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
readfile($cachePath);
