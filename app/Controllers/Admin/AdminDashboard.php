<?php
declare(strict_types=1);

final class AdminDashboard
{
    public function index(): void
    {
        AdminAuth::requireLogin();

        View::render('admin/dashboard', [
            'title' => 'Dashboard — Admin',
            'todayVisitors' => AdminRepository::todayVisitors(),
            'activeSubscriptions' => AdminRepository::activeSubscriptionsCount(),
            'mrr' => AdminRepository::mrr(),
            'pendingCount' => AdminRepository::pendingApprovalsCount(),
            'todayWaClicks' => AdminRepository::todayWaClicks(),
            'regionDemand' => AdminRepository::regionDemand30d(),
            'expiringOwners' => AdminRepository::expiringOwners(5),
        ], 'layout');
    }
}
