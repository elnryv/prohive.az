<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Services\AdminSifarisService;

final class AdminSifarisController
{
    public function siyahi(Request $request): mixed
    {
        try {
            $filtrler = array_filter([
                'status' => $request->query('status'),
                'rayon_id' => $request->query('rayon_id'),
                'bas_tarix' => $request->query('bas_tarix'),
                'bit_tarix' => $request->query('bit_tarix'),
            ], static fn ($v) => $v !== null && $v !== '');

            $data = (new AdminSifarisService())->siyahi(
                $filtrler,
                (int) $request->query('page', 1),
                (int) $request->query('limit', 20)
            );

            return Response::json(['status' => 'ok'] + $data);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function detal(Request $request, array $params): mixed
    {
        try {
            $data = (new AdminSifarisService())->detal((int) $params['id']);

            return Response::json(['status' => 'ok', 'data' => $data]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 404);
        }
    }
}
