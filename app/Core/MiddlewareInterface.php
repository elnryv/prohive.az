<?php

declare(strict_types=1);

namespace App\Core;

interface MiddlewareInterface
{
    /**
     * @param callable $next Zənciri davam etdirmək üçün çağırılır.
     */
    public function handle(Request $request, callable $next): mixed;
}
