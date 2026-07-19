<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/settings.php';

require_method('GET');
maintenance_guard();

$placement = (string) ($_GET['placement'] ?? '');
$allowedPlacements = ['home_top', 'feed', 'profile', 'subscription'];
if (!in_array($placement, $allowedPlacements, true)) {
    json_error('INVALID_PLACEMENT', 'Yer düzgün deyil.', 422);
}

$stmt = db()->prepare(
    "SELECT id, image_path, title, description, link FROM banners
     WHERE placement = :placement AND is_active = 1
       AND (starts_at IS NULL OR starts_at <= NOW())
       AND (ends_at IS NULL OR ends_at >= NOW())
     ORDER BY sort LIMIT 5"
);
$stmt->execute(['placement' => $placement]);

json_ok(['banners' => $stmt->fetchAll()]);
