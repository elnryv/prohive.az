<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\View;

final class DashboardController
{
    public function feed(): void
    {
        Auth::requireRole('driver', '/giris');
        $user = Auth::user();
        // Tam lent (tablar, filtrlər, SSE) FAZA 2/4-də (bölmə 7.2).
        View::render('driver/feed_stub', [
            'pageTitle' => t('nav.feed'),
            'driverStatus' => $user['driver_status'],
            'rejectReason' => $user['reject_reason'],
        ]);
    }
}
