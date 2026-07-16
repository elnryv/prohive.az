<?php
declare(strict_types=1);

/**
 * Ev sorğuları: bütün SQL PDO prepared statements ilə (12.1 — sərbəst SQL qadağan).
 * Görünürlük qaydası 4.3-dəki reyestrə əsaslanır.
 */
final class HouseRepository
{
    private const VISIBILITY_SQL = "
        h.status = 'approved'
        AND (
            (o.billing_status = 'trial' AND o.trial_until >= CURDATE())
            OR (o.billing_status = 'paid' AND o.paid_until >= CURDATE())
            OR (o.billing_status = 'free')
        )
    ";

    /**
     * @param array{
     *   region_id?:int|null, checkin?:string|null, checkout?:string|null, guests?:int|null,
     *   price_min?:float|null, price_max?:float|null, amenity_ids?:int[], sort?:string
     * } $filters
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public static function search(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::buildWhere($filters);
        $order = self::buildOrder($filters['sort'] ?? 'default');

        $countSql = "SELECT COUNT(*) AS c FROM houses h
                      JOIN owners o ON o.id = h.owner_id
                      JOIN regions r ON r.id = h.region_id
                      WHERE {$where}";
        $total = (int) (DB::one($countSql, $params)['c'] ?? 0);

        $maybeFullExpr = self::maybeFullExpr($filters, $params);

        $sql = "SELECT h.*, r.slug AS region_slug, r.name_az AS region_name_az,
                       r.name_ru AS region_name_ru, r.name_en AS region_name_en,
                       (SELECT hp.filename FROM house_photos hp
                         WHERE hp.house_id = h.id AND hp.is_approved = 1
                         ORDER BY hp.is_cover DESC, hp.sort_order ASC LIMIT 1) AS cover_photo,
                       ({$maybeFullExpr}) AS maybe_full
                FROM houses h
                JOIN owners o ON o.id = h.owner_id
                JOIN regions r ON r.id = h.region_id
                WHERE {$where}
                ORDER BY maybe_full ASC, {$order}
                LIMIT :limit OFFSET :offset";

        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private static function buildWhere(array $filters): array
    {
        $conditions = [self::VISIBILITY_SQL];
        $params = [];

        if (!empty($filters['region_id'])) {
            $conditions[] = 'h.region_id = :region_id';
            $params[':region_id'] = (int) $filters['region_id'];
        }
        if (!empty($filters['price_min'])) {
            $conditions[] = 'h.price_night >= :price_min';
            $params[':price_min'] = (float) $filters['price_min'];
        }
        if (!empty($filters['price_max'])) {
            $conditions[] = 'h.price_night <= :price_max';
            $params[':price_max'] = (float) $filters['price_max'];
        }
        if (!empty($filters['guests'])) {
            $conditions[] = 'h.capacity >= :guests';
            $params[':guests'] = (int) $filters['guests'];
        }
        $amenityIds = array_values(array_unique(array_filter(
            $filters['amenity_ids'] ?? [],
            static fn ($id) => (int) $id > 0
        )));
        if ($amenityIds !== []) {
            $placeholders = [];
            foreach ($amenityIds as $i => $id) {
                $ph = ":amenity{$i}";
                $placeholders[] = $ph;
                $params[$ph] = (int) $id;
            }
            $count = count($amenityIds);
            $conditions[] = 'h.id IN (SELECT ha.house_id FROM house_amenities ha
                                       WHERE ha.amenity_id IN (' . implode(',', $placeholders) . ")
                                       GROUP BY ha.house_id
                                       HAVING COUNT(DISTINCT ha.amenity_id) = {$count})";
        }

        return [implode(' AND ', $conditions), $params];
    }

    /** @param array<string,mixed> $params (checkin/checkout param names appended here) */
    private static function maybeFullExpr(array $filters, array &$params): string
    {
        $checkin = $filters['checkin'] ?? null;
        $checkout = $filters['checkout'] ?? null;
        if (!$checkin || !$checkout) {
            return '0';
        }
        try {
            $lastNight = (new DateTimeImmutable($checkout))->modify('-1 day')->format('Y-m-d');
        } catch (Exception) {
            return '0';
        }
        $params[':mf_checkin'] = $checkin;
        $params[':mf_checkout'] = $lastNight;

        return 'SELECT EXISTS(SELECT 1 FROM house_calendar hc
                    WHERE hc.house_id = h.id AND hc.busy_date BETWEEN :mf_checkin AND :mf_checkout)';
    }

    private static function buildOrder(string $sort): string
    {
        return match ($sort) {
            'price_asc' => 'h.price_night ASC',
            'price_desc' => 'h.price_night DESC',
            default => '(h.views_total - DATEDIFF(CURDATE(), DATE(h.created_at)) * 2) DESC, h.created_at DESC',
        };
    }

    /** @return array<string, mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        $sql = "SELECT h.*, r.slug AS region_slug, r.name_az AS region_name_az,
                       r.name_ru AS region_name_ru, r.name_en AS region_name_en,
                       o.full_name AS owner_name, o.created_at AS owner_created_at
                FROM houses h
                JOIN owners o ON o.id = h.owner_id
                JOIN regions r ON r.id = h.region_id
                WHERE h.slug = :slug AND " . self::VISIBILITY_SQL . '
                LIMIT 1';

        return DB::one($sql, [':slug' => $slug]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function photos(int $houseId): array
    {
        return DB::all(
            'SELECT * FROM house_photos WHERE house_id = :id AND is_approved = 1
             ORDER BY is_cover DESC, sort_order ASC, id ASC',
            [':id' => $houseId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function amenities(int $houseId): array
    {
        return DB::all(
            'SELECT a.* FROM amenities a
             JOIN house_amenities ha ON ha.amenity_id = a.id
             WHERE ha.house_id = :id AND a.is_active = 1
             ORDER BY a.sort_order ASC',
            [':id' => $houseId]
        );
    }

    /** @return string[] YYYY-MM-DD busy günlər */
    public static function busyDates(int $houseId, string $fromDate, string $toDate): array
    {
        $rows = DB::all(
            'SELECT busy_date FROM house_calendar
             WHERE house_id = :id AND busy_date BETWEEN :from AND :to',
            [':id' => $houseId, ':from' => $fromDate, ':to' => $toDate]
        );
        return array_map(static fn ($r) => $r['busy_date'], $rows);
    }

    /** @return array<int, array<string, mixed>> */
    public static function popular(int $limit = 6): array
    {
        $sql = 'SELECT h.*, r.slug AS region_slug, r.name_az AS region_name_az,
                       r.name_ru AS region_name_ru, r.name_en AS region_name_en,
                       (SELECT hp.filename FROM house_photos hp
                         WHERE hp.house_id = h.id AND hp.is_approved = 1
                         ORDER BY hp.is_cover DESC, hp.sort_order ASC LIMIT 1) AS cover_photo
                FROM houses h
                JOIN owners o ON o.id = h.owner_id
                JOIN regions r ON r.id = h.region_id
                WHERE ' . self::VISIBILITY_SQL . '
                ORDER BY h.views_total DESC, h.created_at DESC
                LIMIT :limit';

        $stmt = DB::pdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function incrementView(int $houseId): void
    {
        DB::query('UPDATE houses SET views_total = views_total + 1 WHERE id = :id', [':id' => $houseId]);
        self::bumpDailyStat($houseId, 'views');
    }

    public static function incrementWaClick(int $houseId): void
    {
        DB::query('UPDATE houses SET wa_clicks_total = wa_clicks_total + 1 WHERE id = :id', [':id' => $houseId]);
        self::bumpDailyStat($houseId, 'wa_clicks');
    }

    private static function bumpDailyStat(int $houseId, string $column): void
    {
        $column = $column === 'wa_clicks' ? 'wa_clicks' : 'views';
        DB::query(
            "INSERT INTO stats_daily (house_id, stat_date, {$column})
             VALUES (:id, CURDATE(), 1)
             ON DUPLICATE KEY UPDATE {$column} = {$column} + 1",
            [':id' => $houseId]
        );
    }
}
