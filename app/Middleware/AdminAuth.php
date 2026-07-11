<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Env;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Admin sessiyası — bax CLAUDE.md bölmə 8.8: QISA idle-timeout, KALICI DEYİL
 * (müştəri/kuryerdən fərqli olaraq remember-me YOXDUR).
 */
final class AdminAuth implements MiddlewareInterface
{
    private const SESSION_KEY = '_admin_last_activity';

    public function handle(Request $request, callable $next): mixed
    {
        $adminId = Session::get('admin_id');
        $lastActivity = Session::get(self::SESSION_KEY);
        $idleSeconds = Env::getInt('ADMIN_SESSION_IDLE_MINUTES', 15) * 60;

        if ($adminId === null || $lastActivity === null || (time() - (int) $lastActivity) > $idleSeconds) {
            Session::destroy();
            Response::json(['error' => 'Admin sessiyası bitib, yenidən giriş edin'], 401);
        }

        Session::set(self::SESSION_KEY, time());

        return $next($request);
    }
}
