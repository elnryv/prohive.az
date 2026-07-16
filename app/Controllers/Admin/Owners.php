<?php
declare(strict_types=1);

/** Ev sahibləri siyahısı + axtarış (bölmə 9.3, Q11). */
final class Owners
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        AdminAuth::requireLogin();

        $query = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = OwnerRepository::search($query, self::PER_PAGE, self::PER_PAGE * ($page - 1));

        View::render('admin/owners', [
            'title' => 'Ev sahibləri — Admin',
            'query' => $query,
            'owners' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'hasMore' => self::PER_PAGE * ($page - 1) + count($result['items']) < $result['total'],
        ], 'layout');
    }
}
