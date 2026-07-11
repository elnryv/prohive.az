<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Services\BannerService;

final class AdminBannerController
{
    public function yarat(Request $request): mixed
    {
        try {
            $storageDir = dirname(__DIR__, 2) . '/storage/banners';
            $banner = (new BannerService())->yarat($request->all(), $request->file('sekil'), $storageDir);

            return Response::json(['status' => 'ok', 'data' => $banner], 201);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function siyahi(Request $request): mixed
    {
        $data = (new BannerService())->siyahi(
            (int) $request->query('page', 1),
            (int) $request->query('limit', 20)
        );

        return Response::json(['status' => 'ok'] + $data);
    }

    public function aktivlikDeyis(Request $request, array $params): mixed
    {
        try {
            $aktiv = filter_var($request->input('aktiv', false), FILTER_VALIDATE_BOOLEAN);
            (new BannerService())->aktivlikDeyis((int) $params['id'], $aktiv);

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 404);
        }
    }
}
