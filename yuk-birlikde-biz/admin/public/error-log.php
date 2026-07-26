<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

$level = (string) ($_GET['level'] ?? '');
$source = trim((string) ($_GET['source'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;

$where = ['1=1'];
$params = [];
if ($level !== '') {
    $where[] = 'level = :level';
    $params['level'] = $level;
}
if ($source !== '') {
    $where[] = 'source LIKE :source';
    $params['source'] = "%{$source}%";
}
$whereSql = implode(' AND ', $where);

$total = (int) db_scalar($db, "SELECT COUNT(*) FROM error_logs WHERE {$whereSql}", $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT * FROM error_logs WHERE {$whereSql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$logs = $stmt->fetchAll();

admin_header('Xəta jurnalı', $session);
?>
<div class="card">
  <form class="filters" method="get">
    <select name="level">
      <option value="">Bütün səviyyələr</option>
      <?php foreach (['error', 'warning', 'info'] as $l): ?>
        <option value="<?= h($l) ?>" <?= $level === $l ? 'selected' : '' ?>><?= h($l) ?></option>
      <?php endforeach; ?>
    </select>
    <input class="input" type="text" name="source" placeholder="Mənbə (məs: payriff_callback)" value="<?= h($source) ?>">
    <button class="btn" type="submit">Filtrlə</button>
  </form>

  <?php if ($logs === []): ?>
    <div class="empty-state">Xəta qeydi yoxdur.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Səviyyə</th><th>Mənbə</th><th>Mesaj</th><th>Tarix</th></tr></thead>
    <tbody>
      <?php foreach ($logs as $l): ?>
      <tr>
        <td><?= badge($l['level'], $l['level'] === 'error' ? 'error' : ($l['level'] === 'warning' ? 'warning' : 'muted')) ?></td>
        <td><?= h($l['source']) ?></td>
        <td><?= h(mb_substr($l['message'], 0, 200)) ?><?php if ($l['context']): ?><br><small style="color:var(--text-muted);"><?= h($l['context']) ?></small><?php endif; ?></td>
        <td><?= h($l['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php paginate($page, $totalPages, 'error-log.php?level=' . urlencode($level) . '&source=' . urlencode($source)); ?>
  <?php endif; ?>
</div>
<?php
admin_footer();
