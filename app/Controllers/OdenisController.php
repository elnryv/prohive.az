<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Models\Kurye;
use App\Services\OdenisService;

/**
 * Bax CLAUDE.md Əlavə A: POST /odenis/basla (kuryer), POST /webhook/odenis (sistem).
 *
 * /odenis/qayit HƏM kuryerin brauzerinin ödənişdən sonra düşdüyü səhifədir
 * (GET, sessiya tələb edir), HƏM DƏ Payriff-in nəticəni server-tərəfdə POST
 * etdiyi ünvandır (POST, sessiyasız — bax PayriffProvider qeydi: "callback və
 * return URL eynidir").
 */
final class OdenisController
{
    public function basla(Request $request): mixed
    {
        try {
            $userId = (int) Session::get('user_id');
            $kurye = (new Kurye())->findByUserId($userId);

            if ($kurye === null) {
                return Response::json(['error' => 'Kuryer profili tapılmadı'], 404);
            }

            $data = (new OdenisService())->basla((int) $kurye['id']);

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function webhook(Request $request): mixed
    {
        try {
            (new OdenisService())->webhookIsle($request->all(), $request->ip());

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /odenis/qayit — Payriff-in server-tərəfdə payload göndərdiyi eyni
     * ünvan (bax sinif qeydi). webhookIsle ilə eyni idempotent məntiqi paylaşır.
     */
    public function qayitCallback(Request $request): mixed
    {
        try {
            (new OdenisService())->webhookIsle($request->all(), $request->ip());
        } catch (ValidationException) {
            // Sakitcə keçilir — brauzer paralel GET ilə eyni səhifəyə düşür,
            // vəziyyət oradan JS polling ilə göstərilir.
        }

        return Response::json(['status' => 'ok']);
    }

    /**
     * GET /odenis/son-hal — kuryerin öz ən son ödənişinin vəziyyətini JSON
     * olaraq qaytarır (təsdiq səhifəsindəki polling üçün).
     */
    public function sonHal(Request $request): mixed
    {
        try {
            $userId = (int) Session::get('user_id');
            $kurye = (new Kurye())->findByUserId($userId);

            if ($kurye === null) {
                return Response::json(['error' => 'Kuryer profili tapılmadı'], 404);
            }

            $data = (new OdenisService())->sonHal((int) $kurye['id']);

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 404);
        }
    }
}
