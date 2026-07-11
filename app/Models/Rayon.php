<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Rayon
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function aktivSehirde(int $rayonId, int $sehirId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM rayonlar WHERE id = :rayon_id AND sehir_id = :sehir_id AND aktiv = 1'
        );
        $stmt->execute(['rayon_id' => $rayonId, 'sehir_id' => $sehirId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param int[] $rayonIds
     * @return int[] Aktiv olan və mövcud olan rayon ID-ləri
     */
    public function existingActiveIds(array $rayonIds): array
    {
        if ($rayonIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($rayonIds), '?'));
        $stmt = $this->db->prepare("SELECT id FROM rayonlar WHERE id IN ({$placeholders}) AND aktiv = 1");
        $stmt->execute(array_values($rayonIds));

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
