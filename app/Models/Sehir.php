<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Şəhərlər — bax CLAUDE.md bölmə 4.4 və 8.7 (Ərazi İdarəsi).
 */
final class Sehir
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(string $adAz, ?string $adRu, ?string $adEn): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sehirler (ad_az, ad_ru, ad_en, aktiv) VALUES (:ad_az, :ad_ru, :ad_en, 1)'
        );
        $stmt->execute(['ad_az' => $adAz, 'ad_ru' => $adRu, 'ad_en' => $adEn]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sehirler WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function listAll(): array
    {
        return $this->db->query('SELECT * FROM sehirler ORDER BY ad_az')->fetchAll();
    }

    /**
     * Müştəri/kuryer tərəfindəki açar seçicilər üçün — yalnız aktiv şəhərlər
     * (bax bölmə 5.2, 7.1.1, 7.2.2).
     */
    public function aktivListAll(): array
    {
        return $this->db->query('SELECT * FROM sehirler WHERE aktiv = 1 ORDER BY ad_az')->fetchAll();
    }

    public function existsByAd(string $adAz): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM sehirler WHERE ad_az = :ad_az');
        $stmt->execute(['ad_az' => $adAz]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
