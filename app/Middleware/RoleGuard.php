<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Rol-əsaslı giriş qoruması (IDOR qoruması ilə yanaşı — bax CLAUDE.md bölmə 10.2).
 * Konkret rollar üçün alt-siniflər istifadə olunur (bax MusteriGuard, KuryeGuard).
 */
abstract class RoleGuard implements MiddlewareInterface
{
    /** @return string[] */
    abstract protected function allowedRoles(): array;

    public function handle(Request $request, callable $next): mixed
    {
        $rol = Session::get('rol');

        if (!is_string($rol) || !in_array($rol, $this->allowedRoles(), true)) {
            Response::json(['error' => 'Bu əməliyyat üçün icazəniz yoxdur'], 403);
        }

        return $next($request);
    }
}
