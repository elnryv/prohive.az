<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Services\AbunelikService;

final class AdminAbunelikController
{
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
}
