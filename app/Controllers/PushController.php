<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\PushAbune;

/**
 * Bax CLAUDE.md bölmə 9.3: POST /push/abune, POST /push/legv.
 */
final class PushController
{
    public function abune(Request $request): mixed
    {
        $userId = (int) Session::get('user_id');
        $endpoint = (string) $request->input('endpoint', '');
        $p256dh = (string) $request->input('p256dh', '');
        $auth = (string) $request->input('auth', '');

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return Response::json(['error' => 'endpoint/p256dh/auth mütləqdir.'], 422);
        }

        (new PushAbune())->create($userId, $endpoint, $p256dh, $auth);

        return Response::json(['status' => 'ok']);
    }

    public function legv(Request $request): mixed
    {
        $endpoint = (string) $request->input('endpoint', '');

        if ($endpoint !== '') {
            (new PushAbune())->deleteByEndpoint($endpoint);
        }

        return Response::json(['status' => 'ok']);
    }
}
