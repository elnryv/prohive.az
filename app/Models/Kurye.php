<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Kurye
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $userId, ?string $neqliyyat): int
    {
        $stmt = $this->db->prepare('INSERT INTO kuryeler (user_id, neqliyyat) VALUES (:user_id, :neqliyyat)');
        $stmt->execute(['user_id' => $userId, 'neqliyyat' => $neqliyyat]);

        return (int) $this->db->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM kuryeler WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM kuryeler WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function setOnlayn(int $kuryeId, bool $onlayn): void
    {
        $stmt = $this->db->prepare('UPDATE kuryeler SET onlayn = :onlayn WHERE id = :id');
        $stmt->execute(['onlayn' => $onlayn ? 1 : 0, 'id' => $kuryeId]);
    }

    public function incrementTamamlanan(int $kuryeId): void
    {
        $stmt = $this->db->prepare('UPDATE kuryeler SET tamamlanan = tamamlanan + 1 WHERE id = :id');
        $stmt->execute(['id' => $kuryeId]);
    }

    public function setSekil(int $kuryeId, string $sekil): void
    {
        $stmt = $this->db->prepare('UPDATE kuryeler SET sekil = :sekil WHERE id = :id');
        $stmt->execute(['sekil' => $sekil, 'id' => $kuryeId]);
    }

    /**
     * Admin Dashboard sayğacı — bax bölmə 8.1.
     */
    public function countOnlayn(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM kuryeler WHERE onlayn = 1')->fetchColumn();
    }
}
