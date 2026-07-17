<?php

declare(strict_types=1);

/**
 * Qlobal namespace-də köməkçi funksiyalar (view şablonları namespace elan
 * etmədiyi üçün App\Core\... daxilində olsaydı tapılmazdı).
 */

use App\Core\Icon;
use App\Core\Lang;
use App\Core\View;

function t(string $key, array $params = []): string
{
    return Lang::t($key, $params);
}

/** Emoji əvəzinə tək-cizgili SVG ikon (bax App\Core\Icon) — çıxış escape edilmir, çünki öz aramızda sabit SVG-dir. */
function icon(string $name, string $class = 'icon', int $size = 20): string
{
    return Icon::render($name, $class, $size);
}

function e(?string $value): string
{
    return View::e($value);
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return t('common.just_now');
    }
    if ($diff < 3600) {
        return t('common.minutes_ago', ['n' => intdiv($diff, 60)]);
    }
    if ($diff < 86400) {
        return t('common.hours_ago', ['n' => intdiv($diff, 3600)]);
    }
    return t('common.days_ago', ['n' => intdiv($diff, 86400)]);
}
