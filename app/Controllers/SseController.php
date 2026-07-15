<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Kurye;
use App\Services\SifarisService;

/**
 * Canlı lövhə (Server-Sent Events) — bax CLAUDE.md bölmə 6.2.
 */
final class SseController
{
    /** fastcgi_read_timeout (deploy/nginx/*.conf) ilə uzlaşan təhlükəsizlik həddi. */
    private const MAX_ITERATIONS = 1800;
    private const SLEEP_SECONDS = 2;

    public function lovhe(Request $request): void
    {
        $userId = (int) Session::get('user_id');
        $rol = (string) Session::get('rol');

        $kurye = (new Kurye())->findByUserId($userId);
        if ($kurye === null) {
            Response::json(['error' => 'Kuryer profili tapılmadı'], 404);
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        Session::writeClose();

        $service = new SifarisService();
        $lastId = (int) ($request->header('Last-Event-ID') ?? 0);

        $i = 0;
        while (!connection_aborted() && $i < self::MAX_ITERATIONS) {
            $yeni = $service->lovheYenileri((int) $kurye['id'], $rol, $lastId);

            foreach ($yeni as $sifaris) {
                echo "id: {$sifaris['id']}\n";
                echo "event: yeni_sifaris\n";
                echo 'data: ' . json_encode($sifaris, JSON_UNESCAPED_UNICODE) . "\n\n";
                $lastId = (int) $sifaris['id'];
            }

            @ob_flush();
            @flush();

            $i++;
            sleep(self::SLEEP_SECONDS);
        }
    }
}
