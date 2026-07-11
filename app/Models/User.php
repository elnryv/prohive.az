<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByTelefon(string $telefon): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE telefon = :telefon LIMIT 1');
        $stmt->execute(['telefon' => $telefon]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function existsByTelefon(string $telefon): bool
    {
        return $this->findByTelefon($telefon) !== null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (ad, soyad, telefon, parol_hash, whatsapp, whatsapp_tip, rol, dil, sozlesme_qebul, sozlesme_tarix)
             VALUES (:ad, :soyad, :telefon, :parol_hash, :whatsapp, :whatsapp_tip, :rol, :dil, 1, NOW())'
        );

        $stmt->execute([
            'ad' => $data['ad'],
            'soyad' => $data['soyad'],
            'telefon' => $data['telefon'],
            'parol_hash' => $data['parol_hash'],
            'whatsapp' => $data['whatsapp'],
            'whatsapp_tip' => $data['whatsapp_tip'],
            'rol' => $data['rol'],
            'dil' => $data['dil'],
        ]);

        return (int) $this->db->lastInsertId();
    }
}
