<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\View;

final class LogController
{
    public function index(): void
    {
        AdminAuth::requireLogin('/giris');

        $stmt = DB::conn()->query(
            "SELECT l.*, a.username FROM admin_logs l JOIN admins a ON a.id = l.admin_id
             ORDER BY l.created_at DESC LIMIT 200"
        );

        View::render('admin/logs', ['pageTitle' => 'Loglar', 'logs' => $stmt->fetchAll()], 'layouts/admin');
    }
}
