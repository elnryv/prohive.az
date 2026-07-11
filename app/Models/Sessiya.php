<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Remember-me (kalıcı giriş) token idarəsi — bax CLAUDE.md bölmə 7.3.1.
 */
final class Sessiya
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $userId, string $tokenHash, ?string $cihaz): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sessiyalar (user_id, token_hash, cihaz) VALUES (:user_id, :token_hash, :cihaz)'
        );
        $stmt->execute(['user_id' => $userId, 'token_hash' => $tokenHash, 'cihaz' => $cihaz]);

        return (int) $this->db->lastInsertId();
    }

    public function findByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sessiyalar WHERE token_hash = :token_hash LIMIT 1');
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function touch(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE sessiyalar SET son_giris = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function deleteByTokenHash(string $tokenHash): void
    {
        $stmt = $this->db->prepare('DELETE FROM sessiyalar WHERE token_hash = :token_hash');
        $stmt->execute(['token_hash' => $tokenHash]);
    }
}
