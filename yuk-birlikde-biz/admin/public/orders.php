<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

$status = (string) ($_GET['status'] ?? '');
$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1=1'];
$params = [];
if ($status !== '') {
    $where[] = 'o.status = :status';
    $params['status'] = $status;
}
if ($search !== '') {
    $where[] = 'o.number LIKE :q';
    $params['q'] = "%{$search}%";
}
$whereSql = implode(' AND ', $where);

$total = (int) db_scalar($db, "SELECT COUNT(*) FROM orders o WHERE {$whereSql}", $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT o.id, o.number, o.status, o.from_city, o.to_city, o.date_time, o.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS customer_name
     FROM orders o JOIN users u ON u.id = o.customer_id
     WHERE {$whereSql} ORDER BY o.id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusLabels = ['active' => ['Aktiv', 'success'], 'waiting' => ['Təklif gözləyir', 'warning'], 'negotiating' => ['Danışıq gedir', 'warning'], 'closed' => ['Bağlanıb', 'success'], 'cancelled' => ['Ləğv olunub', 'muted'], 'expired' => ['Müddəti bitib', 'error']];

admin_header('Elanlar', $session);
?>
<div class="card">
  <form class="filters" method="get">
    <input class="input" type="text" name="q" placeholder="Elan nömrəsi" value="<?= h($search) ?>">
    <select name="status">
      <option value="">Bütün statuslar</option>
      <?php foreach ($statusLabels as $key => [$label, $_]): ?>
        <option value="<?= h($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtrlə</button>
  </form>

  <?php if ($orders === []): ?>
    <div class="empty-state">Nəticə tapılmadı.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Nömrə</th><th>Müştəri</th><th>Marşrut</th><th>Tarix</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): [$label, $type] = $statusLabels[$o['status']] ?? [$o['status'], 'muted']; ?>
      <tr>
        <td><?= h($o['number']) ?></td>
        <td><?= h($o['customer_name']) ?></td>
        <td><?= h($o['from_city']) ?> → <?= h($o['to_city']) ?></td>
        <td><?= h($o['date_time']) ?></td>
        <td><?= badge($label, $type) ?></td>
        <td><a class="btn btn-sm" href="order-detail.php?id=<?= (int) $o['id'] ?>">Bax</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php paginate($page, $totalPages, 'orders.php?q=' . urlencode($search) . '&status=' . urlencode($status)); ?>
  <?php endif; ?>
</div>
<?php
admin_footer();
