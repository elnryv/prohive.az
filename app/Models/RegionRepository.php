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

    // ===================== Admin CRUD (9.6) =====================

    /** @return array<int, array<string,mixed>> bütün bölgələr (aktiv/deaktiv daxil), real ev sayı ilə */
    public static function listAll(): array
    {
        return DB::all(
            'SELECT r.*, (SELECT COUNT(*) FROM houses h WHERE h.region_id = r.id) AS house_count
             FROM regions r
             ORDER BY r.sort_order ASC, r.name_az ASC'
        );
    }

    public static function findById(int $id): ?array
    {
        return DB::one('SELECT * FROM regions WHERE id = :id', [':id' => $id]);
    }

    /** @param array{slug:string,name_az:string,name_ru:string,name_en:string,tagline_az:?string,tagline_ru:?string,tagline_en:?string,sort_order:int,is_active:int} $data */
    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO regions (slug, name_az, name_ru, name_en, tagline_az, tagline_ru, tagline_en, sort_order, is_active, created_at)
             VALUES (:slug, :name_az, :name_ru, :name_en, :tagline_az, :tagline_ru, :tagline_en, :sort_order, :is_active, NOW())',
            self::colonize($data)
        );
        return (int) DB::lastInsertId();
    }

    /** @param array{slug:string,name_az:string,name_ru:string,name_en:string,tagline_az:?string,tagline_ru:?string,tagline_en:?string,sort_order:int,is_active:int} $data */
    public static function update(int $id, array $data): void
    {
        DB::query(
            'UPDATE regions SET slug = :slug, name_az = :name_az, name_ru = :name_ru, name_en = :name_en,
                    tagline_az = :tagline_az, tagline_ru = :tagline_ru, tagline_en = :tagline_en,
                    sort_order = :sort_order, is_active = :is_active
             WHERE id = :id',
            self::colonize($data) + [':id' => $id]
        );
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private static function colonize(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $out[':' . ltrim($k, ':')] = $v;
        }
        return $out;
    }

    /** true = silindi, false = bağlı ev olduğu üçün silinmədi */
    public static function delete(int $id): bool
    {
        $count = (int) (DB::one('SELECT COUNT(*) AS n FROM houses WHERE region_id = :id', [':id' => $id])['n'] ?? 0);
        if ($count > 0) {
            return false;
        }
        DB::query('DELETE FROM regions WHERE id = :id', [':id' => $id]);
        return true;
    }
}
