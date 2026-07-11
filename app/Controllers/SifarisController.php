<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Models\Kurye;
use App\Services\SifarisService;

/**
 * Bax CLAUDE.md Əlavə A: /sifaris/* marşrutları (müştəri + kuryer).
 */
final class SifarisController
{
    public function yarat(Request $request): mixed
    {
        try {
            $musteriId = (int) Session::get('user_id');
            $sifaris = (new SifarisService())->yarat($request->all(), $musteriId);

            return Response::json(['status' => 'ok', 'data' => $sifaris], 201);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function legv(Request $request, array $params): mixed
    {
        try {
            $musteriId = (int) Session::get('user_id');
            (new SifarisService())->legv((int) $params['id'], $musteriId);

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function tarixce(Request $request): mixed
    {
        $musteriId = (int) Session::get('user_id');

        return Response::json(['status' => 'ok', 'data' => (new SifarisService())->tarixce($musteriId)]);
    }

    public function gotur(Request $request, array $params): mixed
    {
        try {
            $kuryeId = self::currentKuryeId();
            $rol = (string) Session::get('rol');
            $data = (new SifarisService())->gotur((int) $params['id'], $kuryeId, $rol, $request->ip());

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 409);
        }
    }

    public function tamamla(Request $request, array $params): mixed
    {
        try {
            $kuryeId = self::currentKuryeId();
            (new SifarisService())->tamamla((int) $params['id'], $kuryeId);

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function kuryeAktivIsler(Request $request): mixed
    {
        $kuryeId = self::currentKuryeId();

        return Response::json(['status' => 'ok', 'data' => (new SifarisService())->kuryeAktivIsler($kuryeId)]);
    }

    private static function currentKuryeId(): int
    {
        $userId = (int) Session::get('user_id');
        $kurye = (new Kurye())->findByUserId($userId);

        if ($kurye === null) {
            Response::json(['error' => 'Kuryer profili tapılmadı'], 404);
        }

        return (int) $kurye['id'];
    }
}
