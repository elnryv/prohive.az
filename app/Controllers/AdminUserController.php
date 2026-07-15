<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Services\AdminUserService;

final class AdminUserController
{
    public function musteriler(Request $request): mixed
    {
        $service = new AdminUserService();
        $data = $service->musteriler(
            $this->axtarParam($request),
            (int) $request->query('page', 1),
            (int) $request->query('limit', 20)
        );

        return Response::json(['status' => 'ok'] + $data);
    }

    public function kuryerler(Request $request): mixed
    {
        $service = new AdminUserService();
        $data = $service->kuryerler(
            $this->axtarParam($request),
            (int) $request->query('page', 1),
            (int) $request->query('limit', 20)
        );

        return Response::json(['status' => 'ok'] + $data);
    }

    public function detal(Request $request, array $params): mixed
    {
        try {
            $data = (new AdminUserService())->detal((int) $params['id']);

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 404);
        }
    }

    public function blokla(Request $request, array $params): mixed
    {
        return $this->statusDeyis($request, $params, true);
    }

    public function blokdanCixar(Request $request, array $params): mixed
    {
        return $this->statusDeyis($request, $params, false);
    }

    private function statusDeyis(Request $request, array $params, bool $blokla): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            $sebeb = (string) $request->input('sebeb', '');
            $service = new AdminUserService();

            if ($blokla) {
                $service->blokla((int) $params['id'], $sebeb, $adminId, $request->ip());
            } else {
                $service->blokdanCixar((int) $params['id'], $sebeb, $adminId, $request->ip());
            }

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    private function axtarParam(Request $request): ?string
    {
        $axtar = $request->query('axtar');

        return is_string($axtar) && $axtar !== '' ? $axtar : null;
    }
}
