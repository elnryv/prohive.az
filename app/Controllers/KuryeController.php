<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Models\Kurye;
use App\Models\User;
use App\Services\AbunelikService;
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

    public function bolgelerimGoster(Request $request): mixed
    {
        $kurye = self::currentKurye();

        return Response::json(['status' => 'ok', 'data' => (new SifarisService())->kuryeBolgeleri((int) $kurye['id'])]);
    }

    public function profilim(Request $request): mixed
    {
        $kurye = self::currentKurye();
        $user = (new User())->findById((int) $kurye['user_id']);

        return Response::json(['status' => 'ok', 'data' => [
            'ad' => $user['ad'] ?? null,
            'soyad' => $user['soyad'] ?? null,
            'neqliyyat' => $kurye['neqliyyat'],
            'onlayn' => (bool) $kurye['onlayn'],
            'tamamlanan' => (int) $kurye['tamamlanan'],
            'sekil' => $kurye['sekil'],
        ]]);
    }

    public function sekilYukle(Request $request): mixed
    {
        try {
            $kurye = self::currentKurye();
            $storageDir = dirname(__DIR__, 2) . '/storage/kurye-sekiller';
            $adFayl = (new SifarisService())->sekilYukle((int) $kurye['id'], $request->file('sekil'), $storageDir);

            return Response::json(['status' => 'ok', 'data' => ['sekil' => $adFayl]]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function abuneligim(Request $request): mixed
    {
        try {
            $kurye = self::currentKurye();
            $data = (new AbunelikService())->status((int) $kurye['id']);

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 404);
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
