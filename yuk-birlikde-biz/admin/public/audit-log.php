<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

$action = trim((string) ($_GET['action'] ?? ''));
$entity = trim((string) ($_GET['entity'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;

$where = ['1=1'];
$params = [];
if ($action !== '') {
    $where[] = 'a.action LIKE :action';
    $params['action'] = "%{$action}%";
}
if ($entity !== '') {
    $where[] = 'a.entity = :entity';
    $params['entity'] = $entity;
}
$whereSql = implode(' AND ', $where);

$total = (int) db_scalar($db, "SELECT COUNT(*) FROM audit_logs a WHERE {$whereSql}", $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT a.*, ad.username FROM audit_logs a JOIN admin_users ad ON ad.id = a.admin_id
     WHERE {$whereSql} ORDER BY a.id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

admin_header('Audit jurnalı', $session);
?>
<div class="card">
  <form class="filters" method="get">
    <input class="input" type="text" name="action" placeholder="Əməliyyat (məs: subscription_grant)" value="<?= h($action) ?>">
    <input class="input" type="text" name="entity" placeholder="Varlıq (məs: orders)" value="<?= h($entity) ?>">
    <button class="btn" type="submit">Filtrlə</button>
  </form>

  <?php if ($logs === []): ?>
    <div class="empty-state">Qeyd yoxdur.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Admin</th><th>Əməliyyat</th><th>Varlıq</th><th>ID</th><th>IP</th><th>Tarix</th></tr></thead>
    <tbody>
      <?php foreach ($logs as $l): ?>
      <tr>
        <td><?= h($l['username']) ?></td>
        <td><?= h($l['action']) ?></td>
        <td><?= h($l['entity']) ?></td>
        <td><?= $l['entity_id'] !== null ? (int) $l['entity_id'] : '—' ?></td>
        <td><?= h($l['ip']) ?></td>
        <td><?= h($l['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php paginate($page, $totalPages, 'audit-log.php?action=' . urlencode($action) . '&entity=' . urlencode($entity)); ?>
  <?php endif; ?>
</div>
<?php
admin_footer();
