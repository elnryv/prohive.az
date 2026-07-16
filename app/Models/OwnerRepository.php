<?php
declare(strict_types=1);

final class OwnerRepository
{
    public static function findByPhone(string $normalizedPhone): ?array
    {
        return DB::one('SELECT * FROM owners WHERE phone = :p', [':p' => $normalizedPhone]);
    }

    public static function findById(int $id): ?array
    {
        return DB::one('SELECT * FROM owners WHERE id = :id', [':id' => $id]);
    }

    public static function create(string $normalizedPhone, string $passwordHash, string $fullName, int $trialDays): int
    {
        DB::query(
            "INSERT INTO owners (phone, password_hash, full_name, billing_status, trial_until, created_at)
             VALUES (:phone, :hash, :name, 'trial', CURDATE() + INTERVAL :days DAY, NOW())",
            [
                ':phone' => $normalizedPhone,
                ':hash' => $passwordHash,
                ':name' => $fullName,
                ':days' => $trialDays,
            ]
        );
        return (int) DB::lastInsertId();
    }

    public static function touchLastLogin(int $ownerId): void
    {
        DB::query('UPDATE owners SET last_login_at = NOW() WHERE id = :id', [':id' => $ownerId]);
    }

    /** Görünürlük qaydasına (4.3) uyğun ev sahibi aktivdirmi (evləri saytda görünür) */
    public static function isVisible(array $owner): bool
    {
        return match ($owner['billing_status']) {
            'trial' => $owner['trial_until'] !== null && $owner['trial_until'] >= date('Y-m-d'),
            'paid' => $owner['paid_until'] !== null && $owner['paid_until'] >= date('Y-m-d'),
            'free' => true,
            default => false,
        };
    }

    public static function effectivePrice(array $owner): float
    {
        if ($owner['custom_price'] !== null) {
            return (float) $owner['custom_price'];
        }
        return SettingsRepository::getFloat('default_monthly_price', 25.0);
    }

    // ===================== Admin (bölmə 9.3, Q9, Q11) =====================

    /**
     * Telefon (hissəvi, rəqəmlər çıxarılıb) + ad/ev adı üzrə axtarış (Q11).
     * @return array{items: array<int,array<string,mixed>>, total:int}
     */
    public static function search(string $query, int $limit, int $offset): array
    {
        $query = trim($query);
        $digits = Phone::digitsOnly($query);

        $conditions = [];
        $params = [];
        if ($digits !== '') {
            $conditions[] = 'o.phone LIKE :phone_like';
            $params[':phone_like'] = '%' . $digits . '%';
        }
        if ($query !== '') {
            // PDO native prepares (EMULATE_PREPARES=false) dəstəkləmir eyni adlı
            // parametrin bir sorğuda təkrarını — hər occurrence üçün ayrı ad lazımdır.
            $conditions[] = '(o.full_name LIKE :text_like1 OR h.title LIKE :text_like2)';
            $params[':text_like1'] = '%' . $query . '%';
            $params[':text_like2'] = '%' . $query . '%';
        }
        $where = $conditions !== [] ? ('WHERE ' . implode(' OR ', $conditions)) : '';

        $countSql = "SELECT COUNT(DISTINCT o.id) AS n FROM owners o LEFT JOIN houses h ON h.owner_id = o.id {$where}";
        $total = (int) (DB::one($countSql, $params)['n'] ?? 0);

        $sql = "SELECT o.*,
                       (SELECT COUNT(*) FROM houses hh WHERE hh.owner_id = o.id) AS house_count
                FROM owners o
                LEFT JOIN houses h ON h.owner_id = o.id
                {$where}
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function setFree(int $id): void
    {
        DB::query("UPDATE owners SET billing_status = 'free' WHERE id = :id", [':id' => $id]);
    }

    /** "Pullu et": free/blocked-dan normal (trial/paid/expired) tsiklə qaytarır; qiymət də təyin edilə bilər */
    public static function makeBillable(int $id, ?float $customPrice): void
    {
        $owner = self::findById($id);
        if ($owner === null) {
            return;
        }

        $updates = [];
        $params = [':id' => $id];

        if (in_array($owner['billing_status'], ['free', 'blocked'], true)) {
            $updates[] = 'billing_status = :status';
            $params[':status'] = self::naturalStatusFromDates($owner);
        }
        if ($customPrice !== null) {
            $updates[] = 'custom_price = :price';
            $params[':price'] = $customPrice;
        }
        if ($updates === []) {
            return;
        }
        DB::query('UPDATE owners SET ' . implode(', ', $updates) . ' WHERE id = :id', $params);
    }

    public static function clearCustomPrice(int $id): void
    {
        DB::query('UPDATE owners SET custom_price = NULL WHERE id = :id', [':id' => $id]);
    }

    public static function block(int $id): void
    {
        DB::query("UPDATE owners SET billing_status = 'blocked' WHERE id = :id", [':id' => $id]);
    }

    public static function activate(int $id): void
    {
        $owner = self::findById($id);
        if ($owner === null) {
            return;
        }
        DB::query(
            'UPDATE owners SET billing_status = :status WHERE id = :id',
            [':status' => self::naturalStatusFromDates($owner), ':id' => $id]
        );
    }

    private static function naturalStatusFromDates(array $owner): string
    {
        $today = date('Y-m-d');
        if ($owner['trial_until'] !== null && $owner['trial_until'] >= $today) {
            return 'trial';
        }
        if ($owner['paid_until'] !== null && $owner['paid_until'] >= $today) {
            return 'paid';
        }
        return 'expired';
    }

    /** @return array<int, array<string,mixed>> */
    public static function paymentsHistory(int $ownerId): array
    {
        return DB::all(
            'SELECT * FROM payments WHERE owner_id = :id ORDER BY created_at DESC',
            [':id' => $ownerId]
        );
    }

    /** @return array<int, array<string,mixed>> */
    public static function houses(int $ownerId): array
    {
        return HouseRepository::forOwner($ownerId);
    }
}
