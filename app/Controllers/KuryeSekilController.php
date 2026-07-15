<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Kuryer/yükdaşıma profil şəkillərini storage/kurye-sekiller/-dan (nginx-də
 * bloklanmış qovluq) təhlükəsiz şəkildə yayımlayır — bax BannerImageController
 * ilə eyni naming/təhlükəsizlik qaydası.
 */
final class KuryeSekilController
{
    private const CONTENT_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    public function goster(Request $request, array $params): never
    {
        $fayl = basename((string) ($params['fayl'] ?? ''));

        if (!preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/', $fayl)) {
            Response::json(['error' => 'Şəkil tapılmadı'], 404);
        }

        $yol = dirname(__DIR__, 2) . '/storage/kurye-sekiller/' . $fayl;

        if (!is_file($yol)) {
            Response::json(['error' => 'Şəkil tapılmadı'], 404);
        }

        $uzantı = strtolower((string) pathinfo($fayl, PATHINFO_EXTENSION));

        header('Content-Type: ' . (self::CONTENT_TYPES[$uzantı] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . (string) filesize($yol));
        readfile($yol);
        exit;
    }
}
