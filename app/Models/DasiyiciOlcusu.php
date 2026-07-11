<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class DasiyiciOlcusu
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @param int[] $olcuIds
     */
    public function attachSizes(int $kuryeId, array $olcuIds): void
    {
        $stmt = $this->db->prepare('INSERT INTO dasiyici_olculeri (kurye_id, olcu_id) VALUES (:kurye_id, :olcu_id)');

        foreach ($olcuIds as $olcuId) {
            $stmt->execute(['kurye_id' => $kuryeId, 'olcu_id' => $olcuId]);
        }
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
}
