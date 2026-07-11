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

    /**
     * Admin ərazi idarəsi — bax bölmə 8.7.
     */
    public function create(int $sehirId, string $adAz, ?string $adRu, ?string $adEn): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO rayonlar (sehir_id, ad_az, ad_ru, ad_en, aktiv) VALUES (:sehir_id, :ad_az, :ad_ru, :ad_en, 1)'
        );
        $stmt->execute(['sehir_id' => $sehirId, 'ad_az' => $adAz, 'ad_ru' => $adRu, 'ad_en' => $adEn]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM rayonlar WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function listBySehir(int $sehirId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM rayonlar WHERE sehir_id = :sehir_id ORDER BY ad_az');
        $stmt->execute(['sehir_id' => $sehirId]);

        return $stmt->fetchAll();
    }

    /**
     * Müştəri/kuryer tərəfindəki açar seçicilər üçün — yalnız aktiv ərazilər
     * (bax bölmə 5.2, 7.1.1, 7.2.2, TERR-8.7).
     */
    public function aktivListBySehir(int $sehirId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM rayonlar WHERE sehir_id = :sehir_id AND aktiv = 1 ORDER BY ad_az'
        );
        $stmt->execute(['sehir_id' => $sehirId]);

        return $stmt->fetchAll();
    }

    public function existsByAdInSehir(int $sehirId, string $adAz): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM rayonlar WHERE sehir_id = :sehir_id AND ad_az = :ad_az'
        );
        $stmt->execute(['sehir_id' => $sehirId, 'ad_az' => $adAz]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function setAktiv(int $id, bool $aktiv): void
    {
        $stmt = $this->db->prepare('UPDATE rayonlar SET aktiv = :aktiv WHERE id = :id');
        $stmt->execute(['aktiv' => $aktiv ? 1 : 0, 'id' => $id]);
    }

    public function isUsedInSifarisler(int $id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM sifarisler WHERE goturulme_rayon_id = :id OR catdirilma_rayon_id = :id2'
        );
        $stmt->execute(['id' => $id, 'id2' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function isUsedInKuryeBolgeler(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM kurye_bolgeler WHERE rayon_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM rayonlar WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
