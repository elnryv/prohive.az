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
 * Bax CLAUDE.md Əlavə A: POST /kurye/onlayn. Ərazi seçimi (POST /kurye/bolgeler)
 * Əlavə A cədvəlində yoxdur, lakin bölmə 7.2.2/5.3-də təsvir olunan rayon filtrini
 * işlək etmək üçün zəruridir — bu səbəbdən Faza 3 çərçivəsində əlavə olundu.
 */
final class KuryeController
{
    public function onlayn(Request $request): mixed
    {
        $kurye = self::currentKurye();
        $onlayn = filter_var($request->input('onlayn', false), FILTER_VALIDATE_BOOLEAN);

        (new SifarisService())->onlaynToggle((int) $kurye['id'], $onlayn);

        return Response::json(['status' => 'ok', 'onlayn' => $onlayn]);
    }

    public function bolgeler(Request $request): mixed
    {
        try {
            $kurye = self::currentKurye();
            $rayonlar = $request->input('rayonlar', []);

            if (!is_array($rayonlar)) {
                return Response::json(['error' => 'Rayonlar massiv formatında olmalıdır.'], 422);
            }

            (new SifarisService())->bolgelerYenile((int) $kurye['id'], $rayonlar);

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    private static function currentKurye(): array
    {
        $userId = (int) Session::get('user_id');
        $kurye = (new Kurye())->findByUserId($userId);

        if ($kurye === null) {
            Response::json(['error' => 'Kuryer profili tapılmadı'], 404);
        }

        return $kurye;
    }
}
