<?php

declare(strict_types=1);

/**
 * Qlobal namespace-də köməkçi funksiyalar (view şablonları namespace elan
 * etmədiyi üçün App\Core\... daxilində olsaydı tapılmazdı).
 */

use App\Core\Lang;
use App\Core\View;

function t(string $key, array $params = []): string
{
    return Lang::t($key, $params);
}

function e(?string $value): string
{
    return View::e($value);
}
