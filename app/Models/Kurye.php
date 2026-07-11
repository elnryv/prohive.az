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
}
