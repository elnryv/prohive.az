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

    /**
     * Admin panel — müştəri/kuryer tabları (bax bölmə 8.2).
     *
     * @param string[] $rollar
     */
    public function listByRoller(array $rollar, ?string $axtar, int $limit, int $offset): array
    {
        [$where, $params] = $this->rolAxtarSharti($rollar, $axtar);

        $sql = "SELECT id, ad, soyad, telefon, rol, status, created_at
                FROM users WHERE {$where}
                ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param string[] $rollar
     */
    public function countByRoller(array $rollar, ?string $axtar): int
    {
        [$where, $params] = $this->rolAxtarSharti($rollar, $axtar);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE {$where}");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Admin popup detalı — users + (varsa) kuryeler cədvəllərinin birləşməsi (bax bölmə 8.2).
     */
    public function findDetail(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, k.id AS kurye_id, k.neqliyyat, k.onlayn, k.tamamlanan
             FROM users u
             LEFT JOIN kuryeler k ON k.user_id = u.id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE users SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Admin Dashboard sayğacı — bax bölmə 8.1.
     */
    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE status = :status');
        $stmt->execute(['status' => $status]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param string[] $rollar
     * @return array{0: string, 1: array<string, string>}
     */
    private function rolAxtarSharti(array $rollar, ?string $axtar): array
    {
        $rolPlaceholders = [];
        $params = [];

        foreach ($rollar as $i => $rol) {
            $key = "rol{$i}";
            $rolPlaceholders[] = ":{$key}";
            $params[$key] = $rol;
        }

        $where = 'rol IN (' . implode(',', $rolPlaceholders) . ')';

        if ($axtar !== null && $axtar !== '') {
            // Qeyd: Database EMULATE_PREPARES=false istifadə edir (native MySQL prepare),
            // ona görə eyni adlı placeholder təkrar istifadə oluna bilmir — hər occurrence
            // üçün ayrı adla bağlanır.
            $where .= ' AND (ad LIKE :axtar1 OR soyad LIKE :axtar2 OR telefon LIKE :axtar3)';
            $params['axtar1'] = '%' . $axtar . '%';
            $params['axtar2'] = '%' . $axtar . '%';
            $params['axtar3'] = '%' . $axtar . '%';
        }

        return [$where, $params];
    }
}
