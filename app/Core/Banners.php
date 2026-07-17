<?php

declare(strict_types=1);

namespace App\Core;

/** Admin-də yüklənən reklam bannerlərinin ictimai (müştəri/sürücü) tərəfdən oxunması. */
final class Banners
{
    public static function active(): array
    {
        return DB::conn()
            ->query('SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order, id')
            ->fetchAll();
    }
}
