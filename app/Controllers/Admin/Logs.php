<?php
declare(strict_types=1);

/** admin_logs görüntüləyici (9.7): filtr admin/action/tarix. */
final class Logs
{
    private const PER_PAGE = 40;

    public function index(): void
    {
        AdminAuth::requireLogin();

        $adminId = isset($_GET['admin_id']) && $_GET['admin_id'] !== '' ? (int) $_GET['admin_id'] : null;
        $action = trim((string) ($_GET['action'] ?? ''));
        $date = trim((string) ($_GET['date'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = AdminRepository::logs($adminId, $action, $date, self::PER_PAGE, self::PER_PAGE * ($page - 1));

        View::render('admin/logs', [
            'title' => 'Loglar — Admin',
            'logs' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'hasMore' => self::PER_PAGE * ($page - 1) + count($result['items']) < $result['total'],
            'actions' => AdminRepository::distinctActions(),
            'filterAdminId' => $adminId,
            'filterAction' => $action,
            'filterDate' => $date,
        ], 'layout');
    }
}
