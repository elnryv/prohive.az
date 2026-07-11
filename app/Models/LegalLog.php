<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Append-only audit jurnalı — bax CLAUDE.md bölmə 4.9. Yalnız INSERT, UPDATE/DELETE
 * bu sinifdə HEÇ VAXT tətbiq olunmamalıdır.
 */
final class LegalLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function yaz(?int $actorId, ?int $sifarisId, string $hadise, array $detal, string $ip): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO legal_logs (actor_id, sifaris_id, hadise, detal_json, ip_adres)
             VALUES (:actor_id, :sifaris_id, :hadise, :detal_json, :ip_adres)'
        );

        $stmt->execute([
            'actor_id' => $actorId,
            'sifaris_id' => $sifarisId,
            'hadise' => $hadise,
            'detal_json' => json_encode($detal, JSON_UNESCAPED_UNICODE),
            'ip_adres' => @inet_pton($ip) ?: null,
        ]);
    }
}
