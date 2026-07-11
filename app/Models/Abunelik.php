<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Kuryer abunə dövrləri — bax CLAUDE.md bölmə 4.7 və 8.5.
 */
final class Abunelik
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Kuryerin ən son (indiki) abunə dövrü — vəziyyətdən asılı olmayaraq.
     */
    public function sonuncu(int $kuryeId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM abunelikler WHERE kurye_id = :kurye_id ORDER BY bitme DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['kurye_id' => $kuryeId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(int $kuryeId, string $tip, string $baslama, string $bitme): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO abunelikler (kurye_id, tip, baslama, bitme, aktiv) VALUES (:kurye_id, :tip, :baslama, :bitme, 1)'
        );
        $stmt->execute(['kurye_id' => $kuryeId, 'tip' => $tip, 'baslama' => $baslama, 'bitme' => $bitme]);

        return (int) $this->db->lastInsertId();
    }

    public function uzatBitme(int $id, string $yeniBitme): void
    {
        $stmt = $this->db->prepare('UPDATE abunelikler SET bitme = :bitme, aktiv = 1 WHERE id = :id');
        $stmt->execute(['bitme' => $yeniBitme, 'id' => $id]);
    }

    public function setTip(int $id, string $tip): void
    {
        $stmt = $this->db->prepare('UPDATE abunelikler SET tip = :tip WHERE id = :id');
        $stmt->execute(['tip' => $tip, 'id' => $id]);
    }

    public function setAktiv(int $id, bool $aktiv): void
    {
        $stmt = $this->db->prepare('UPDATE abunelikler SET aktiv = :aktiv WHERE id = :id');
        $stmt->execute(['aktiv' => $aktiv ? 1 : 0, 'id' => $id]);
    }

    /**
     * cron/abunelik_yoxla.php — bitmiş amma hələ aktiv işarələnmiş dövrlər
     * (bax bölmə 9.1.2: "Ödəniş vaxtında edilməsə → profil bağlanır").
     * Hər kuryerin YALNIZ ən son dövrü nəzərə alınır (window function).
     */
    public function bitmisAmmaAktivOlanlar(): array
    {
        $sql = "SELECT * FROM (
                    SELECT a.*, ROW_NUMBER() OVER (PARTITION BY a.kurye_id ORDER BY a.bitme DESC, a.id DESC) AS rn
                    FROM abunelikler a
                ) t
                WHERE t.rn = 1 AND t.aktiv = 1 AND t.bitme < CURDATE()";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * cron/abunelik_yoxla.php — tezliklə (gunIci gün ərzində) bitəcək aktiv dövrlər.
     */
    public function tezliklaBitecekler(int $gunIci): array
    {
        $sql = "SELECT * FROM (
                    SELECT a.*, ROW_NUMBER() OVER (PARTITION BY a.kurye_id ORDER BY a.bitme DESC, a.id DESC) AS rn
                    FROM abunelikler a
                ) t
                WHERE t.rn = 1 AND t.aktiv = 1
                  AND t.bitme BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :gun DAY)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('gun', $gunIci, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
