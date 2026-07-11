<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use App\Services\AuthService;

/**
 * Bax CLAUDE.md Əlavə A: POST /qeydiyyat, POST /giris, POST /cixis.
 */
final class AuthController
{
    public function qeydiyyat(Request $request): mixed
    {
        try {
            $service = new AuthService();
            $result = $service->qeydiyyat($request->all(), $request->ip());

            return Response::json(['status' => 'ok', 'data' => $result], 201);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function giris(Request $request): mixed
    {
        try {
            $service = new AuthService();
            $rememberMe = filter_var($request->input('meni_xatirla', false), FILTER_VALIDATE_BOOLEAN);

            $result = $service->giris(
                (string) $request->input('telefon', ''),
                (string) $request->input('parol', ''),
                $rememberMe,
                $request->ip(),
                $request->header('User-Agent')
            );

            if ($result['remember_token'] !== null) {
                self::setRememberCookie($result['remember_token']);
            }

            return Response::json(['status' => 'ok', 'rol' => $result['user']['rol']]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 401);
        }
    }

    public function cixis(Request $request): mixed
    {
        $cookieName = Env::get('REMEMBER_ME_COOKIE', 'birlikde_remember');
        $rememberToken = $_COOKIE[$cookieName] ?? null;

        $service = new AuthService();
        $service->cixis(is_string($rememberToken) ? $rememberToken : null);

        if (is_string($rememberToken)) {
            setcookie($cookieName, '', ['expires' => time() - 3600, 'path' => '/']);
        }

        return Response::json(['status' => 'ok']);
    }

    private static function setRememberCookie(string $token): void
    {
        $days = Env::getInt('REMEMBER_ME_DAYS', 365);

        setcookie(Env::get('REMEMBER_ME_COOKIE', 'birlikde_remember'), $token, [
            'expires' => time() + ($days * 86400),
            'path' => '/',
            'secure' => Env::getBool('SESSION_SECURE', true),
            'httponly' => true,
            'samesite' => Env::get('SESSION_SAMESITE', 'Lax'),
        ]);
    }
}
