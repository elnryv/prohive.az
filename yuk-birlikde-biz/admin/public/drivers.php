<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();

$search = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ["u.role = 'driver'"];
$params = [];
if ($search !== '') {
    $where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE :q OR u.phone LIKE :q2)";
    $params['q'] = "%{$search}%";
    $params['q2'] = "%{$search}%";
}
if ($status !== '') {
    $where[] = 'u.status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$db = db();
$total = (int) db_scalar($db, "SELECT COUNT(*) FROM users u WHERE {$whereSql}", $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT u.id, u.first_name, u.last_name, u.phone, u.status, u.created_at,
            d.rating_avg, d.completed_count,
            (SELECT ends_at FROM subscriptions s WHERE s.driver_id = u.id AND s.status = 'active' AND s.ends_at > NOW() ORDER BY s.ends_at DESC LIMIT 1) AS sub_ends_at
     FROM users u JOIN drivers d ON d.user_id = u.id
     WHERE {$whereSql} ORDER BY u.id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$drivers = $stmt->fetchAll();

$statusLabels = ['active' => ['Aktiv', 'success'], 'temp_blocked' => ['Müvəqqəti bloklanıb', 'warning'], 'blocked' => ['Bloklanıb', 'error'], 'deleted' => ['Silinib', 'muted']];

admin_header('Sürücülər', $session);
?>
<div class="card">
  <form class="filters" method="get">
    <input class="input" type="text" name="q" placeholder="Ad və ya nömrə" value="<?= h($search) ?>">
    <select name="status">
      <option value="">Bütün statuslar</option>
      <?php foreach ($statusLabels as $key => [$label, $_]): ?>
        <option value="<?= h($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtrlə</button>
  </form>

  <?php if ($drivers === []): ?>
    <div class="empty-state">Nəticə tapılmadı.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Ad Soyad</th><th>Telefon</th><th>Status</th><th>Reytinq</th><th>Tamamlanmış</th><th>Abunə</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($drivers as $d): [$label, $type] = $statusLabels[$d['status']] ?? [$d['status'], 'muted']; ?>
      <tr>
        <td><?= h($d['first_name'] . ' ' . $d['last_name']) ?></td>
        <td>+<?= h($d['phone']) ?></td>
        <td><?= badge($label, $type) ?></td>
        <td><?= $d['rating_avg'] !== null ? h((string) $d['rating_avg']) : '—' ?></td>
        <td><?= (int) $d['completed_count'] ?></td>
        <td><?= $d['sub_ends_at'] ? badge('Aktiv (' . $d['sub_ends_at'] . ')', 'success') : badge('Yoxdur', 'muted') ?></td>
        <td><a class="btn btn-sm" href="driver-detail.php?id=<?= (int) $d['id'] ?>">Bax</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php paginate($page, $totalPages, 'drivers.php?q=' . urlencode($search) . '&status=' . urlencode($status)); ?>
  <?php endif; ?>
</div>
<?php
admin_footer();
