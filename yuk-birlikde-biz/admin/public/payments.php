<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

$status = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;

$where = ['1=1'];
$params = [];
if ($status !== '') {
    $where[] = 'p.status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$total = (int) db_scalar($db, "SELECT COUNT(*) FROM payments p WHERE {$whereSql}", $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT p.id, p.amount, p.currency, p.payriff_tx_id, p.status, p.raw, p.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS driver_name, p.driver_id
     FROM payments p JOIN users u ON u.id = p.driver_id
     WHERE {$whereSql} ORDER BY p.id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$statusLabels = ['success' => ['Uğurlu', 'success'], 'failed' => ['Uğursuz', 'error'], 'pending' => ['Gözləyir', 'warning']];

admin_header('Ödənişlər (Payriff)', $session);
?>
<div class="card">
  <form class="filters" method="get">
    <select name="status">
      <option value="">Bütün statuslar</option>
      <?php foreach ($statusLabels as $key => [$label, $_]): ?>
        <option value="<?= h($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtrlə</button>
  </form>

  <?php if ($payments === []): ?>
    <div class="empty-state">Nəticə tapılmadı.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Tranzaksiya</th><th>Sürücü</th><th>Məbləğ</th><th>Status</th><th>Tarix</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($payments as $p): [$label, $type] = $statusLabels[$p['status']] ?? [$p['status'], 'muted']; ?>
      <tr>
        <td><?= h($p['payriff_tx_id'] ?? '—') ?></td>
        <td><a href="driver-detail.php?id=<?= (int) $p['driver_id'] ?>"><?= h($p['driver_name']) ?></a></td>
        <td><?= h((string) $p['amount']) ?> <?= h($p['currency']) ?></td>
        <td><?= badge($label, $type) ?></td>
        <td><?= h($p['created_at']) ?></td>
        <td><?php if ($p['raw']): ?><a class="btn btn-sm" href="#" onclick="document.getElementById('raw-<?= (int) $p['id'] ?>').style.display='block';return false;">Raw JSON</a><?php endif; ?></td>
      </tr>
      <?php if ($p['raw']): ?>
      <tr id="raw-<?= (int) $p['id'] ?>" style="display:none;"><td colspan="6"><pre style="white-space:pre-wrap;font-size:12px;background:#F8FAFC;padding:12px;border-radius:8px;"><?= h(json_encode(json_decode($p['raw']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre></td></tr>
      <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php paginate($page, $totalPages, 'payments.php?status=' . urlencode($status)); ?>
  <?php endif; ?>
</div>
<?php
admin_footer();
