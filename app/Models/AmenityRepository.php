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
}
