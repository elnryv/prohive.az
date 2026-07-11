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

    /**
     * Admin panel — sifariş idarəsi (bax bölmə 8.3): filtr + pagination.
     *
     * @param array{status?:string, rayon_id?:int, bas_tarix?:string, bit_tarix?:string} $filtrler
     */
    public function listForAdmin(array $filtrler, int $limit, int $offset): array
    {
        [$where, $params] = $this->adminFiltrSharti($filtrler);

        $sql = "SELECT s.*, mu.ad AS musteri_ad, mu.soyad AS musteri_soyad, mu.telefon AS musteri_telefon,
                       ku.ad AS kurye_ad, ku.soyad AS kurye_soyad, ku.telefon AS kurye_telefon
                FROM sifarisler s
                JOIN users mu ON mu.id = s.musteri_id
                LEFT JOIN kuryeler k ON k.id = s.kurye_id
                LEFT JOIN users ku ON ku.id = k.user_id
                WHERE {$where}
                ORDER BY s.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countForAdmin(array $filtrler): int
    {
        [$where, $params] = $this->adminFiltrSharti($filtrler);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sifarisler s WHERE {$where}");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function findDetailForAdmin(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, mu.ad AS musteri_ad, mu.soyad AS musteri_soyad, mu.telefon AS musteri_telefon,
                    ku.ad AS kurye_ad, ku.soyad AS kurye_soyad, ku.telefon AS kurye_telefon
             FROM sifarisler s
             JOIN users mu ON mu.id = s.musteri_id
             LEFT JOIN kuryeler k ON k.id = s.kurye_id
             LEFT JOIN users ku ON ku.id = k.user_id
             WHERE s.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function adminFiltrSharti(array $filtrler): array
    {
        $where = '1=1';
        $params = [];

        if (!empty($filtrler['status'])) {
            $where .= ' AND s.status = :status';
            $params['status'] = $filtrler['status'];
        }
        if (!empty($filtrler['rayon_id'])) {
            $where .= ' AND s.goturulme_rayon_id = :rayon_id';
            $params['rayon_id'] = (int) $filtrler['rayon_id'];
        }
        if (!empty($filtrler['bas_tarix'])) {
            $where .= ' AND s.created_at >= :bas_tarix';
            $params['bas_tarix'] = $filtrler['bas_tarix'] . ' 00:00:00';
        }
        if (!empty($filtrler['bit_tarix'])) {
            $where .= ' AND s.created_at <= :bit_tarix';
            $params['bit_tarix'] = $filtrler['bit_tarix'] . ' 23:59:59';
        }

        return [$where, $params];
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
