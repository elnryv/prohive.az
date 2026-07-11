<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sadə şablon renderer — framework yoxdur, PHP include-əsaslı Views qatı
 * (bax CLAUDE.md qovluq strukturu: "Views/ Şablonlar + dil").
 */
final class View
{
    public static function render(string $template, array $params = [], int $status = 200): never
    {
        $path = dirname(__DIR__) . '/Views/' . $template . '.php';

        if (!is_file($path)) {
            throw new \RuntimeException("View tapılmadı: {$template}");
        }

        $params['t'] = static fn (string $key): string => Lang::get($key);
        $params['dil'] = Lang::current();

        Response::view($path, $params, $status);
    }
}
