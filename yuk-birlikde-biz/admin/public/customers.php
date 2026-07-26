<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();

$search = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ["role = 'customer'"];
$params = [];
if ($search !== '') {
    $where[] = "(CONCAT(first_name, ' ', last_name) LIKE :q OR phone LIKE :q2)";
    $params['q'] = "%{$search}%";
    $params['q2'] = "%{$search}%";
}
if ($status !== '') {
    $where[] = 'status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$db = db();
$total = (int) db_scalar($db, "SELECT COUNT(*) FROM users WHERE {$whereSql}", $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT id, first_name, last_name, phone, status, created_at, last_seen_at FROM users WHERE {$whereSql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$customers = $stmt->fetchAll();

$statusLabels = ['active' => ['Aktiv', 'success'], 'temp_blocked' => ['Müvəqqəti bloklanıb', 'warning'], 'blocked' => ['Bloklanıb', 'error'], 'deleted' => ['Silinib', 'muted']];

admin_header('Müştərilər', $session);
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

  <?php if ($customers === []): ?>
    <div class="empty-state">Nəticə tapılmadı.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Ad Soyad</th><th>Telefon</th><th>Status</th><th>Qeydiyyat</th><th>Son aktivlik</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($customers as $c): [$label, $type] = $statusLabels[$c['status']] ?? [$c['status'], 'muted']; ?>
      <tr>
        <td><?= h($c['first_name'] . ' ' . $c['last_name']) ?></td>
        <td>+<?= h($c['phone']) ?></td>
        <td><?= badge($label, $type) ?></td>
        <td><?= h($c['created_at']) ?></td>
        <td><?= h($c['last_seen_at'] ?? '—') ?></td>
        <td><a class="btn btn-sm" href="customer-detail.php?id=<?= (int) $c['id'] ?>">Bax</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php paginate($page, $totalPages, 'customers.php?q=' . urlencode($search) . '&status=' . urlencode($status)); ?>
  <?php endif; ?>
</div>
<?php
admin_footer();
