<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Ödəniş əməliyyatları — bax CLAUDE.md bölmə 4.7 və 9.1.
 */
final class Odenis
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $kuryeId, string $orderId, float $mebleg, string $provayder): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO odenisler (kurye_id, order_id, mebleg, status, provayder)
             VALUES (:kurye_id, :order_id, :mebleg, \'gozlemede\', :provayder)'
        );
        $stmt->execute([
            'kurye_id' => $kuryeId,
            'order_id' => $orderId,
            'mebleg' => $mebleg,
            'provayder' => $provayder,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByOrderId(string $orderId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM odenisler WHERE order_id = :order_id LIMIT 1');
        $stmt->execute(['order_id' => $orderId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findPendingByKurye(int $kuryeId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM odenisler WHERE kurye_id = :kurye_id AND status = 'gozlemede'
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute(['kurye_id' => $kuryeId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function updateStatus(int $id, string $status, array $callback): void
    {
        $stmt = $this->db->prepare(
            'UPDATE odenisler SET status = :status, callback_json = :callback_json WHERE id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'callback_json' => json_encode($callback, JSON_UNESCAPED_UNICODE),
            'id' => $id,
        ]);
    }
}
