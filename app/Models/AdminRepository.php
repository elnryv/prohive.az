<?php
declare(strict_types=1);

final class AdminRepository
{
    public static function findByUsername(string $username): ?array
    {
        return DB::one('SELECT * FROM admins WHERE username = :u', [':u' => $username]);
    }

    public static function touchLastLogin(int $adminId): void
    {
        DB::query('UPDATE admins SET last_login_at = NOW() WHERE id = :id', [':id' => $adminId]);
    }

    // ===================== Dashboard statistikası (9.1) =====================

    public static function todayVisitors(): int
    {
        return (int) (DB::one(
            'SELECT COALESCE(SUM(views), 0) AS n FROM stats_daily WHERE stat_date = CURDATE()'
        )['n'] ?? 0);
    }

    public static function todayWaClicks(): int
    {
        return (int) (DB::one(
            'SELECT COALESCE(SUM(wa_clicks), 0) AS n FROM stats_daily WHERE stat_date = CURDATE()'
        )['n'] ?? 0);
    }

    public static function activeSubscriptionsCount(): int
    {
        return (int) (DB::one(
            "SELECT COUNT(*) AS n FROM owners WHERE
                billing_status = 'free'
                OR (billing_status = 'trial' AND trial_until >= CURDATE())
                OR (billing_status = 'paid' AND paid_until >= CURDATE())"
        )['n'] ?? 0);
    }

    /** MRR: paid statuslu (aktiv) sahiblərin effektiv qiymətlərinin cəmi */
    public static function mrr(): float
    {
        $rows = DB::all(
            "SELECT custom_price FROM owners WHERE billing_status = 'paid' AND paid_until >= CURDATE()"
        );
        $default = SettingsRepository::getFloat('default_monthly_price', 25.0);
        $sum = 0.0;
        foreach ($rows as $r) {
            $sum += $r['custom_price'] !== null ? (float) $r['custom_price'] : $default;
        }
        return $sum;
    }

    public static function pendingApprovalsCount(): int
    {
        return (int) (DB::one("SELECT COUNT(*) AS n FROM houses WHERE status = 'pending'")['n'] ?? 0)
            + (int) (DB::one('SELECT COUNT(*) AS n FROM house_photos WHERE is_approved = 0')['n'] ?? 0);
    }

    /** @return array<int, array{name_az:string, views:int, wa_clicks:int}> son 30 gün, bölgə üzrə */
    public static function regionDemand30d(): array
    {
        return DB::all(
            "SELECT r.name_az, COALESCE(SUM(s.views), 0) AS views, COALESCE(SUM(s.wa_clicks), 0) AS wa_clicks
             FROM regions r
             LEFT JOIN houses h ON h.region_id = r.id
             LEFT JOIN stats_daily s ON s.house_id = h.id AND s.stat_date >= CURDATE() - INTERVAL 29 DAY
             WHERE r.is_active = 1
             GROUP BY r.id, r.name_az
             ORDER BY views DESC"
        );
    }

    // ===================== Loglar (9.7) =====================

    /** @return array{items: array<int,array<string,mixed>>, total:int} */
    public static function logs(?int $adminId, ?string $action, ?string $date, int $limit, int $offset): array
    {
        $conditions = [];
        $params = [];
        if ($adminId !== null) {
            $conditions[] = 'l.admin_id = :admin_id';
            $params[':admin_id'] = $adminId;
        }
        if ($action !== null && $action !== '') {
            $conditions[] = 'l.action = :action';
            $params[':action'] = $action;
        }
        if ($date !== null && $date !== '') {
            $conditions[] = 'DATE(l.created_at) = :date';
            $params[':date'] = $date;
        }
        $where = $conditions !== [] ? ('WHERE ' . implode(' AND ', $conditions)) : '';

        $total = (int) (DB::one("SELECT COUNT(*) AS n FROM admin_logs l {$where}", $params)['n'] ?? 0);

        $sql = "SELECT l.*, a.username FROM admin_logs l
                JOIN admins a ON a.id = l.admin_id
                {$where}
                ORDER BY l.created_at DESC
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

    /** @return string[] Filtr açılan siyahısı üçün unikal action dəyərləri */
    public static function distinctActions(): array
    {
        $rows = DB::all('SELECT DISTINCT action FROM admin_logs ORDER BY action ASC');
        return array_map(static fn ($r) => $r['action'], $rows);
    }
}
