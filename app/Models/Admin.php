<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Ayrı, izolyasiya olunmuş admin cədvəli — bax CLAUDE.md bölmə 8.8. Web-dən
 * admin qeydiyyatı YOXDUR, yalnız database/create_admin.php CLI ilə yaradılır.
 */
final class Admin
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByTelefon(string $telefon): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM adminler WHERE telefon = :telefon LIMIT 1');
        $stmt->execute(['telefon' => $telefon]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM adminler WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(string $ad, string $soyad, string $telefon, string $parolHash): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO adminler (ad, soyad, telefon, parol_hash) VALUES (:ad, :soyad, :telefon, :parol_hash)'
        );
        $stmt->execute(['ad' => $ad, 'soyad' => $soyad, 'telefon' => $telefon, 'parol_hash' => $parolHash]);

        return (int) $this->db->lastInsertId();
    }

    public function updateParolHash(int $id, string $parolHash): void
    {
        $stmt = $this->db->prepare('UPDATE adminler SET parol_hash = :parol_hash WHERE id = :id');
        $stmt->execute(['parol_hash' => $parolHash, 'id' => $id]);
    }
}
