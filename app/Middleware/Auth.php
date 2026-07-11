<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Env;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Sessiya;
use App\Models\User;

/**
 * Müştəri/kuryer kalıcı sessiyası — bax CLAUDE.md bölmə 7.3.1. Sessiya yoxdursa
 * remember-me cookie ilə avtomatik bərpa cəhdi edilir.
 */
final class Auth implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!Session::has('user_id') && !$this->tryRememberMe()) {
            Response::json(['error' => 'Giriş tələb olunur'], 401);
        }

        return $next($request);
    }

    private function tryRememberMe(): bool
    {
        $cookieName = Env::get('REMEMBER_ME_COOKIE', 'birlikde_remember');
        $token = $_COOKIE[$cookieName] ?? null;

        if (!is_string($token) || $token === '') {
            return false;
        }

        $sessiyaModel = new Sessiya();
        $sessiya = $sessiyaModel->findByTokenHash(hash('sha256', $token));

        if ($sessiya === null) {
            return false;
        }

        $userModel = new User();
        $user = $userModel->findById((int) $sessiya['user_id']);

        if ($user === null || $user['status'] !== 'aktiv') {
            return false;
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('rol', $user['rol']);
        $sessiyaModel->touch((int) $sessiya['id']);

        return true;
    }
}
