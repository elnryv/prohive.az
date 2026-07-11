<?php

declare(strict_types=1);

namespace App\Middleware;

final class MusteriGuard extends RoleGuard
{
    protected function allowedRoles(): array
    {
        return ['musteri'];
    }
}
