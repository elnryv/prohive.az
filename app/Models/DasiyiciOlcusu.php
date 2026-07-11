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
     * @return int[] Kuryerin seçdiyi yükdaşıma ölçü ID-ləri
     */
    public function olcuIdsByKurye(int $kuryeId): array
    {
        $stmt = $this->db->prepare('SELECT olcu_id FROM dasiyici_olculeri WHERE kurye_id = :kurye_id');
        $stmt->execute(['kurye_id' => $kuryeId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
