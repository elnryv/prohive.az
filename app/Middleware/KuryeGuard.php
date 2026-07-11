<?php

declare(strict_types=1);

namespace App\Middleware;

final class KuryeGuard extends RoleGuard
{
    protected function allowedRoles(): array
    {
        return ['kurye', 'yukdasima'];
    }
}
