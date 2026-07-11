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
}
