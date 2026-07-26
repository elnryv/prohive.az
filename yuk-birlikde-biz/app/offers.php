<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/texts.php';

function fetch_offer_or_404(int $id): array
{
    $stmt = db()->prepare('SELECT * FROM offers WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $offer = $stmt->fetch();

    if ($offer === false) {
        json_error('OFFER_NOT_FOUND', text('offer_not_found'), 404);
    }

    return $offer;
}
