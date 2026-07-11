<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Env;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

final class CsrfGuard implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE'], true)) {
            $fieldName = Env::get('CSRF_TOKEN_NAME', 'csrf_token');
            $token = $request->header('X-CSRF-Token') ?? $request->input($fieldName);

            if (!Csrf::verify(is_string($token) ? $token : null)) {
                Response::json(['error' => 'CSRF token yanlışdır və ya vaxtı bitib'], 419);
            }
        }

        return $next($request);
    }
}
