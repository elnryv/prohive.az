<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Web Push (VAPID) abunəlikləri — bax CLAUDE.md bölmə 9.3.3.
 */
final class PushAbune
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $userId, string $endpoint, string $p256dh, string $auth): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO push_abuneler (user_id, endpoint, p256dh, auth) VALUES (:user_id, :endpoint, :p256dh, :auth)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth = VALUES(auth)'
        );
        $stmt->execute(['user_id' => $userId, 'endpoint' => $endpoint, 'p256dh' => $p256dh, 'auth' => $auth]);
    }

    public function deleteByEndpoint(string $endpoint): void
    {
        $stmt = $this->db->prepare('DELETE FROM push_abuneler WHERE endpoint = :endpoint');
        $stmt->execute(['endpoint' => $endpoint]);
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM push_abuneler WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }
}
