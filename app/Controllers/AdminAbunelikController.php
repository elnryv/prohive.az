<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Services\AbunelikService;
use App\Services\PushService;

final class AdminAbunelikController
{
    private const KAMPANIYA_BASLIQ = 'Bu ay abunə haqqı sizin üçün PULSUZDUR';
    private const KAMPANIYA_METN = 'Hörmətli kuryer, Birlikdə platforması olaraq bu ay abunə haqqınızı '
        . 'kampaniya çərçivəsində sizin üçün pulsuz etdik — heç bir əlavə ödəniş etmənizə ehtiyac yoxdur, '
        . 'sadəcə əvvəlki kimi işə davam edə bilərsiniz. Bizimlə olduğunuz üçün təşəkkür edirik. — Birlikdə komandası';

    public function status(Request $request, array $params): mixed
    {
        try {
            $data = (new AbunelikService())->status((int) $params['kuryeId']);

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 404);
        }
    }

    public function uzat(Request $request, array $params): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $gun = (int) $request->input('gun', 30);
            $sebeb = (string) $request->input('sebeb', '');

            $data = (new AbunelikService())->uzat((int) $params['kuryeId'], $gun, $sebeb, $adminId, $request->ip());

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function tipDeyis(Request $request, array $params): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $tip = (string) $request->input('tip', '');
            $sebeb = (string) $request->input('sebeb', '');

            $data = (new AbunelikService())->tipDeyis((int) $params['kuryeId'], $tip, $sebeb, $adminId, $request->ip());

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function aktivlikDeyis(Request $request, array $params): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $aktiv = filter_var($request->input('aktiv', false), FILTER_VALIDATE_BOOLEAN);
            $sebeb = (string) $request->input('sebeb', '');

            $data = (new AbunelikService())->aktivlikDeyis((int) $params['kuryeId'], $aktiv, $sebeb, $adminId, $request->ip());

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function qiymet(Request $request): mixed
    {
        $qiymet = (new AbunelikService())->qiymetiAl();

        return Response::json(['status' => 'ok', 'data' => ['qiymet' => $qiymet]]);
    }

    public function qiymetTeyinEt(Request $request): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $qiymet = (string) $request->input('qiymet', '');
            $sebeb = (string) $request->input('sebeb', '');

            $yeniQiymet = (new AbunelikService())->qiymetiTeyinEt($qiymet, $sebeb, $adminId, $request->ip());

            return Response::json(['status' => 'ok', 'data' => ['qiymet' => $yeniQiymet]]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function qlobalRejim(Request $request): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $rejim = (string) $request->input('rejim', '');
            $sebeb = (string) $request->input('sebeb', '');

            (new AbunelikService())->qlobalRejim($rejim, $sebeb, $adminId, $request->ip());

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Bir düymə ilə BÜTÜN kuryerləri pulsuz/pullu etmə (bax AbunelikService::
     * hamisiniDeyis). "pulsuz" seçilərsə hər kuryerə rəsmi kampaniya bildirişi
     * göndərilir.
     */
    public function hamisiniDeyis(Request $request): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $tip = (string) $request->input('tip', '');
            $sebeb = (string) $request->input('sebeb', '');

            $data = (new AbunelikService())->hamisiniDeyis($tip, $sebeb, $adminId, $request->ip());

            if ($tip === 'pulsuz') {
                $pushService = new PushService();
                foreach ($data['kuryeler'] as $kurye) {
                    $pushService->gonder($kurye['user_id'], self::KAMPANIYA_BASLIQ, self::KAMPANIYA_METN);
                }
            }

            unset($data['kuryeler']);

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }
}
