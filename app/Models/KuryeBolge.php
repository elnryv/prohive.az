<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Kuryerin xidmət etdiyi rayonlar (N—N) — bax CLAUDE.md bölmə 7.2.2 və 5.3.
 */
final class KuryeBolge
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return int[]
     */
    public function getRayonIds(int $kuryeId): array
    {
        $stmt = $this->db->prepare('SELECT rayon_id FROM kurye_bolgeler WHERE kurye_id = :kurye_id');
        $stmt->execute(['kurye_id' => $kuryeId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Kuryerin seçdiyi ərazilər (ad daxil) — profil səhifəsində göstərmək üçün
     * (bax bölmə 7.2.2 "kompakt xülasə").
     */
    public function getRayonlar(int $kuryeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.* FROM kurye_bolgeler kb
             JOIN rayonlar r ON r.id = kb.rayon_id
             WHERE kb.kurye_id = :kurye_id
             ORDER BY r.ad_az'
        );
        $stmt->execute(['kurye_id' => $kuryeId]);

        return $stmt->fetchAll();
    }

    /**
     * Kuryerin ərazi seçimini tam əvəzləyir (əvvəlkiləri silib yenilərini yazır).
     *
     * @param int[] $rayonIds
     */
    public function sync(int $kuryeId, array $rayonIds): void
    {
        $this->db->beginTransaction();

        try {
            $del = $this->db->prepare('DELETE FROM kurye_bolgeler WHERE kurye_id = :kurye_id');
            $del->execute(['kurye_id' => $kuryeId]);

            if ($rayonIds !== []) {
                $ins = $this->db->prepare(
                    'INSERT INTO kurye_bolgeler (kurye_id, rayon_id) VALUES (:kurye_id, :rayon_id)'
                );
                foreach (array_unique($rayonIds) as $rayonId) {
                    $ins->execute(['kurye_id' => $kuryeId, 'rayon_id' => $rayonId]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
