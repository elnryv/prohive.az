<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Reklam bannerləri — bax CLAUDE.md bölmə 4.8 və 8.4.
 */
final class Banner
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bannerler (baslik, sekil_yol, link, hedef, baslama, bitme, sira, aktiv)
             VALUES (:baslik, :sekil_yol, :link, :hedef, :baslama, :bitme, :sira, 1)'
        );
        $stmt->execute([
            'baslik' => $data['baslik'],
            'sekil_yol' => $data['sekil_yol'],
            'link' => $data['link'],
            'hedef' => $data['hedef'],
            'baslama' => $data['baslama'],
            'bitme' => $data['bitme'],
            'sira' => $data['sira'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM bannerler WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function listAll(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM bannerler ORDER BY sira ASC, created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM bannerler')->fetchColumn();
    }

    /**
     * Admin Dashboard sayğacı — bax bölmə 8.1.
     */
    public function countAktiv(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM bannerler WHERE aktiv = 1')->fetchColumn();
    }

    public function setAktiv(int $id, bool $aktiv): void
    {
        $stmt = $this->db->prepare('UPDATE bannerler SET aktiv = :aktiv WHERE id = :id');
        $stmt->execute(['aktiv' => $aktiv ? 1 : 0, 'id' => $id]);
    }
}
