<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;

/**
 * Giriş cəhdlərinə brute-force qoruması (bax CLAUDE.md bölmə LOGIN-2.3).
 */
final class RateLimit implements MiddlewareInterface
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900;

    public function handle(Request $request, callable $next): mixed
    {
        $key = 'rl:' . $request->path() . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            Response::json(['error' => 'Çox sayda cəhd. Bir az sonra yenidən cəhd edin.'], 429);
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        return $next($request);
    }
}
