<?php
declare(strict_types=1);

/**
 * Ödənişlər (9.5). Payriff inteqrasiyası Faza 4-ün işidir (Q7) — bu fazada
 * siyahı/filtr hazırdır, "statusu yenidən yoxla" düyməsi Faza 4-də aktivləşəcək.
 */
final class Payments
{
    private const PER_PAGE = 30;

    public function index(): void
    {
        AdminAuth::requireLogin();

        $status = trim((string) ($_GET['status'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = self::PER_PAGE * ($page - 1);

        $conditions = [];
        $params = [];
        if ($status !== '') {
            $conditions[] = 'p.status = :status';
            $params[':status'] = $status;
        }
        $where = $conditions !== [] ? ('WHERE ' . implode(' AND ', $conditions)) : '';

        $total = (int) (DB::one("SELECT COUNT(*) AS n FROM payments p {$where}", $params)['n'] ?? 0);

        $sql = "SELECT p.*, o.full_name AS owner_name, o.phone AS owner_phone
                FROM payments p
                JOIN owners o ON o.id = p.owner_id
                {$where}
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
        $params[':limit'] = self::PER_PAGE;
        $params[':offset'] = $offset;

        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        View::render('admin/payments', [
            'title' => 'Ödənişlər — Admin',
            'payments' => $stmt->fetchAll(),
            'total' => $total,
            'status' => $status,
            'paymentsEnabled' => PAYRIFF_SECRET_KEY !== '',
        ], 'layout');
    }
}
