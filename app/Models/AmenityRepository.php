<?php
declare(strict_types=1);

final class AmenityRepository
{
    /** @return array<int, array<string, mixed>> */
    public static function listActive(): array
    {
        return DB::all('SELECT * FROM amenities WHERE is_active = 1 ORDER BY sort_order ASC');
    }

    public static function findIdByNameAz(string $nameAz): ?int
    {
        $row = DB::one('SELECT id FROM amenities WHERE name_az = :n AND is_active = 1 LIMIT 1', [':n' => $nameAz]);
        return $row !== null ? (int) $row['id'] : null;
    }

    // ===================== Admin CRUD (9.6) =====================

    /** @return array<int, array<string,mixed>> */
    public static function listAll(): array
    {
        return DB::all(
            'SELECT a.*, (SELECT COUNT(*) FROM house_amenities ha WHERE ha.amenity_id = a.id) AS usage_count
             FROM amenities a ORDER BY a.sort_order ASC'
        );
    }

    public static function findById(int $id): ?array
    {
        return DB::one('SELECT * FROM amenities WHERE id = :id', [':id' => $id]);
    }

    /** @param array{icon:string,name_az:string,name_ru:string,name_en:string,sort_order:int,is_active:int} $data */
    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO amenities (icon, name_az, name_ru, name_en, sort_order, is_active)
             VALUES (:icon, :name_az, :name_ru, :name_en, :sort_order, :is_active)',
            self::colonize($data)
        );
        return (int) DB::lastInsertId();
    }

    /** @param array{icon:string,name_az:string,name_ru:string,name_en:string,sort_order:int,is_active:int} $data */
    public static function update(int $id, array $data): void
    {
        DB::query(
            'UPDATE amenities SET icon = :icon, name_az = :name_az, name_ru = :name_ru, name_en = :name_en,
                    sort_order = :sort_order, is_active = :is_active
             WHERE id = :id',
            self::colonize($data) + [':id' => $id]
        );
    }

    /** true = silindi, false = istifadədə olduğu üçün silinmədi */
    public static function delete(int $id): bool
    {
        $count = (int) (DB::one('SELECT COUNT(*) AS n FROM house_amenities WHERE amenity_id = :id', [':id' => $id])['n'] ?? 0);
        if ($count > 0) {
            return false;
        }
        DB::query('DELETE FROM amenities WHERE id = :id', [':id' => $id]);
        return true;
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
}
