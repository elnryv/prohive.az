<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AdminDashboardService;

final class AdminDashboardController
{
    public function goster(Request $request): mixed
    {
        $service = new AdminDashboardService();

        return Response::json([
            'status' => 'ok',
            'sayğaclar' => $service->sayğaclar(),
            'son_hadiseler' => $service->sonHadiseler(),
        ]);
    }
}
