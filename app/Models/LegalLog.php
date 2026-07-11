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

    /**
     * Admin sifariş detalı — "tarixçə" (status keçidləri) — bax bölmə 8.3.
     */
    public function bySifarisId(int $sifarisId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM legal_logs WHERE sifaris_id = :sifaris_id ORDER BY created_at ASC'
        );
        $stmt->execute(['sifaris_id' => $sifarisId]);

        return $stmt->fetchAll();
    }

    /**
     * Admin Dashboard — "son hadisələr" (bax bölmə 8.1).
     */
    public function sonuncular(int $limit): array
    {
        $stmt = $this->db->prepare('SELECT * FROM legal_logs ORDER BY created_at DESC, id DESC LIMIT :limit');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
