<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Sifarişlər (nüvə cədvəl) — bax CLAUDE.md bölmə 4.6 və 6 (Sifariş Axını).
 */
final class Sifaris
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sifarisler (
                musteri_id, goturulme_sehir_id, goturulme_rayon_id, goturulme_unvan,
                catdirilma_sehir_id, catdirilma_rayon_id, catdirilma_unvan,
                yuk_tesviri, teklif_qiymet, tecili, tip, yukdasima_olcu_id, status
            ) VALUES (
                :musteri_id, :g_sehir, :g_rayon, :g_unvan,
                :c_sehir, :c_rayon, :c_unvan,
                :yuk_tesviri, :teklif_qiymet, :tecili, :tip, :yukdasima_olcu_id, \'axtarisda\'
            )'
        );

        $stmt->execute([
            'musteri_id' => $data['musteri_id'],
            'g_sehir' => $data['goturulme_sehir_id'],
            'g_rayon' => $data['goturulme_rayon_id'],
            'g_unvan' => $data['goturulme_unvan'],
            'c_sehir' => $data['catdirilma_sehir_id'],
            'c_rayon' => $data['catdirilma_rayon_id'],
            'c_unvan' => $data['catdirilma_unvan'],
            'yuk_tesviri' => $data['yuk_tesviri'],
            'teklif_qiymet' => $data['teklif_qiymet'],
            'tecili' => $data['tecili'],
            'tip' => $data['tip'],
            'yukdasima_olcu_id' => $data['yukdasima_olcu_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sifarisler WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function listByMusteri(int $musteriId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM sifarisler WHERE musteri_id = :musteri_id ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue('musteri_id', $musteriId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param int[] $rayonIds
     */
    public function axtarisdaByRayonlar(array $rayonIds, int $lastId): array
    {
        if ($rayonIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($rayonIds), '?'));
        $sql = "SELECT * FROM sifarisler
                WHERE status = 'axtarisda' AND tip = 'kurye'
                  AND goturulme_rayon_id IN ({$placeholders})
                  AND id > ?
                ORDER BY id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$rayonIds, $lastId]);

        return $stmt->fetchAll();
    }

    /**
     * @param int[] $rayonIds
     * @param int[] $olcuIds
     */
    public function axtarisdaByRayonlarVeOlculer(array $rayonIds, array $olcuIds, int $lastId): array
    {
        if ($rayonIds === [] || $olcuIds === []) {
            return [];
        }

        $rayonPlaceholders = implode(',', array_fill(0, count($rayonIds), '?'));
        $olcuPlaceholders = implode(',', array_fill(0, count($olcuIds), '?'));
        $sql = "SELECT * FROM sifarisler
                WHERE status = 'axtarisda' AND tip = 'yukdasima'
                  AND goturulme_rayon_id IN ({$rayonPlaceholders})
                  AND yukdasima_olcu_id IN ({$olcuPlaceholders})
                  AND id > ?
                ORDER BY id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$rayonIds, ...$olcuIds, $lastId]);

        return $stmt->fetchAll();
    }

    /**
     * Atomic götürmə (bax CLAUDE.md bölmə 6.3, RACE-6.3). rowCount()===1 → uğur.
     */
    public function atomicGotur(int $id, int $kuryeId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sifarisler
             SET kurye_id = :kurye_id, status = 'goturulub', goturulme_vaxti = NOW(), version = version + 1
             WHERE id = :id AND status = 'axtarisda' AND kurye_id IS NULL"
        );
        $stmt->execute(['kurye_id' => $kuryeId, 'id' => $id]);

        return $stmt->rowCount() === 1;
    }

    public function complete(int $id, int $kuryeId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sifarisler SET status = 'tamamlandi'
             WHERE id = :id AND kurye_id = :kurye_id AND status = 'goturulub'"
        );
        $stmt->execute(['id' => $id, 'kurye_id' => $kuryeId]);

        return $stmt->rowCount() === 1;
    }

    public function cancel(int $id, int $musteriId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sifarisler SET status = 'legv'
             WHERE id = :id AND musteri_id = :musteri_id AND status IN ('axtarisda', 'goturulub')"
        );
        $stmt->execute(['id' => $id, 'musteri_id' => $musteriId]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Cron: 1 saatdan çox götürülməyən sifarişləri passivləşdirir (bax bölmə 6.1/6.5).
     */
    public function passivlesdirKohneleri(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE sifarisler SET status = 'passiv'
             WHERE status = 'axtarisda' AND created_at < (NOW() - INTERVAL 1 HOUR)"
        );
        $stmt->execute();

        return $stmt->rowCount();
    }
}
