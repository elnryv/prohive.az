<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Services\AdminEraziService;

final class AdminEraziController
{
    public function sehirler(Request $request): mixed
    {
        return Response::json(['status' => 'ok', 'data' => (new AdminEraziService())->sehirSiyahi()]);
    }

    public function sehirYarat(Request $request): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $data = (new AdminEraziService())->sehirYarat(
                (string) $request->input('ad_az', ''),
                $request->input('ad_ru') !== null ? (string) $request->input('ad_ru') : null,
                $request->input('ad_en') !== null ? (string) $request->input('ad_en') : null,
                $adminId,
                $request->ip()
            );

            return Response::json(['status' => 'ok', 'data' => $data], 201);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function rayonlar(Request $request, array $params): mixed
    {
        try {
            $data = (new AdminEraziService())->rayonlarBySehir((int) $params['sehirId']);

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 404);
        }
    }

    public function rayonYarat(Request $request, array $params): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $data = (new AdminEraziService())->rayonYarat(
                (int) $params['sehirId'],
                (string) $request->input('ad_az', ''),
                $request->input('ad_ru') !== null ? (string) $request->input('ad_ru') : null,
                $request->input('ad_en') !== null ? (string) $request->input('ad_en') : null,
                $adminId,
                $request->ip()
            );

            return Response::json(['status' => 'ok', 'data' => $data], 201);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function rayonAktivlik(Request $request, array $params): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $aktiv = filter_var($request->input('aktiv', false), FILTER_VALIDATE_BOOLEAN);
            (new AdminEraziService())->rayonAktivlikDeyis((int) $params['id'], $aktiv, $adminId, $request->ip());

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function rayonSil(Request $request, array $params): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            (new AdminEraziService())->rayonSil((int) $params['id'], $adminId, $request->ip());

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }
}
