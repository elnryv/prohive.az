<?php
declare(strict_types=1);

final class RegionRepository
{
    private const VISIBLE_HOUSE_SQL = "
        h.status = 'approved'
        AND (
            (o.billing_status = 'trial' AND o.trial_until >= CURDATE())
            OR (o.billing_status = 'paid' AND o.paid_until >= CURDATE())
            OR (o.billing_status = 'free')
        )
    ";

    /** @return array<int, array<string, mixed>> aktiv bölgələr + görünən ev sayı */
    public static function listActive(): array
    {
        $sql = 'SELECT r.*,
                       (SELECT COUNT(*) FROM houses h
                         JOIN owners o ON o.id = h.owner_id
                         WHERE h.region_id = r.id AND ' . self::VISIBLE_HOUSE_SQL . ') AS house_count
                FROM regions r
                WHERE r.is_active = 1
                ORDER BY r.sort_order ASC, r.name_az ASC';

        return DB::all($sql);
    }

    public static function findBySlug(string $slug): ?array
    {
        return DB::one('SELECT * FROM regions WHERE slug = :slug AND is_active = 1', [':slug' => $slug]);
    }
}
