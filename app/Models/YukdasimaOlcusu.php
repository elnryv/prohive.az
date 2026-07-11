<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Yükdaşıma ölçü kateqoriyaları kataloqu (XS-Mega) — bax CLAUDE.md bölmə 4.3.2.
 */
final class YukdasimaOlcusu
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @param int[] $olcuIds
     * @return int[] Verilən ID-lərdən DB-də həqiqətən mövcud olanlar
     */
    public function existingIds(array $olcuIds): array
    {
        if ($olcuIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($olcuIds), '?'));
        $stmt = $this->db->prepare("SELECT id FROM yukdasima_olculeri WHERE id IN ({$placeholders})");
        $stmt->execute(array_values($olcuIds));

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function exists(int $id): bool
    {
        return count($this->existingIds([$id])) === 1;
    }
}
