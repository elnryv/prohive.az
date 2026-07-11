<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Qlobal sistem ayarları (bax CLAUDE.md bölmə 4.8 və 8.5.1, məs. abune_rejimi).
 */
final class Ayar
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function get(string $ad): ?string
    {
        $stmt = $this->db->prepare('SELECT deyer FROM ayarlar WHERE ad = :ad LIMIT 1');
        $stmt->execute(['ad' => $ad]);
        $deyer = $stmt->fetchColumn();

        return $deyer === false ? null : (string) $deyer;
    }

    public function set(string $ad, string $deyer): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ayarlar (ad, deyer) VALUES (:ad, :deyer)
             ON DUPLICATE KEY UPDATE deyer = VALUES(deyer)'
        );
        $stmt->execute(['ad' => $ad, 'deyer' => $deyer]);
    }
}
