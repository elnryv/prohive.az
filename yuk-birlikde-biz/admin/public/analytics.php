<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

$from = (string) ($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
$to = (string) ($_GET['to'] ?? date('Y-m-d'));

function daily_series(PDO $db, string $table, string $dateCol, string $from, string $to, string $extraWhere = ''): array
{
    $stmt = $db->prepare(
        "SELECT DATE({$dateCol}) AS d, COUNT(*) AS c FROM {$table}
         WHERE DATE({$dateCol}) BETWEEN :from AND :to {$extraWhere}
         GROUP BY DATE({$dateCol}) ORDER BY d"
    );
    $stmt->execute(['from' => $from, 'to' => $to]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[$r['d']] = (int) $r['c'];
    }

    $series = [];
    $cursor = strtotime($from);
    $end = strtotime($to);
    while ($cursor <= $end) {
        $day = date('Y-m-d', $cursor);
        $series[] = ['date' => $day, 'value' => $map[$day] ?? 0];
        $cursor = strtotime('+1 day', $cursor);
    }
    return $series;
}

$registrations = daily_series($db, 'users', 'created_at', $from, $to);
$closedOrders = daily_series($db, 'orders', 'updated_at', $from, $to, "AND status = 'closed'");
$paymentsSeries = daily_series($db, 'payments', 'created_at', $from, $to, "AND status = 'success'");

$activeUsers = (int) db_scalar($db, "SELECT COUNT(*) FROM users WHERE status = 'active' AND last_seen_at >= :from", ['from' => $from]);
$activeOrders = (int) db_scalar($db, "SELECT COUNT(*) FROM orders WHERE status IN ('active','waiting','negotiating')");
$activeSubs = (int) db_scalar($db, "SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND ends_at > NOW()");
$expiredSubs = (int) db_scalar($db, "SELECT COUNT(*) FROM subscriptions WHERE status = 'expired'");
$paymentsTotal = (float) db_scalar($db, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'success' AND DATE(created_at) BETWEEN :from AND :to", ['from' => $from, 'to' => $to]);
$bannerClicks = $db->query('SELECT title, placement, click_count FROM banners ORDER BY click_count DESC LIMIT 10')->fetchAll();

admin_header('Analitika', $session);
?>
<div class="card">
  <form class="filters" method="get">
    <input class="input" type="date" name="from" value="<?= h($from) ?>">
    <input class="input" type="date" name="to" value="<?= h($to) ?>">
    <button class="btn" type="submit">Filtrlə</button>
  </form>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="label">Aktiv istifadəçilər (aralıqda)</div><div class="value"><?= $activeUsers ?></div></div>
  <div class="stat-card"><div class="label">Aktiv elanlar</div><div class="value"><?= $activeOrders ?></div></div>
  <div class="stat-card"><div class="label">Aktiv abunələr</div><div class="value"><?= $activeSubs ?></div></div>
  <div class="stat-card"><div class="label">Bitmiş abunələr</div><div class="value"><?= $expiredSubs ?></div></div>
  <div class="stat-card"><div class="label">Payriff ödənişləri (aralıqda)</div><div class="value"><?= number_format($paymentsTotal, 2) ?> AZN</div></div>
</div>

<div class="card">
  <h2>Qeydiyyatlar</h2>
  <canvas class="chart" id="chart-registrations"></canvas>
</div>
<div class="card">
  <h2>Bağlanan elanlar</h2>
  <canvas class="chart" id="chart-closed"></canvas>
</div>
<div class="card">
  <h2>Payriff ödənişləri (say)</h2>
  <canvas class="chart" id="chart-payments"></canvas>
</div>

<div class="card">
  <h2>Banner klikləri (top 10)</h2>
  <?php if ($bannerClicks === []): ?><div class="empty-state">Banner yoxdur.</div><?php else: ?>
  <table>
    <thead><tr><th>Başlıq</th><th>Yer</th><th>Klik</th></tr></thead>
    <tbody>
      <?php foreach ($bannerClicks as $b): ?>
      <tr><td><?= h($b['title']) ?></td><td><?= h($b['placement']) ?></td><td><?= (int) $b['click_count'] ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
const registrationsData = <?= json_encode($registrations, JSON_UNESCAPED_UNICODE) ?>;
const closedData = <?= json_encode($closedOrders, JSON_UNESCAPED_UNICODE) ?>;
const paymentsData = <?= json_encode($paymentsSeries, JSON_UNESCAPED_UNICODE) ?>;

function drawBarChart(canvasId, series, color) {
  const canvas = document.getElementById(canvasId);
  const dpr = window.devicePixelRatio || 1;
  const rect = canvas.getBoundingClientRect();
  canvas.width = rect.width * dpr;
  canvas.height = rect.height * dpr;
  const ctx = canvas.getContext('2d');
  ctx.scale(dpr, dpr);

  const w = rect.width, h = rect.height;
  ctx.clearRect(0, 0, w, h);

  const max = Math.max(1, ...series.map((s) => s.value));
  const barWidth = w / series.length;

  series.forEach((point, i) => {
    const barHeight = (point.value / max) * (h - 24);
    ctx.fillStyle = color;
    ctx.fillRect(i * barWidth + 1, h - barHeight - 20, Math.max(1, barWidth - 2), barHeight);
    if (series.length <= 31 || i % Math.ceil(series.length / 15) === 0) {
      ctx.fillStyle = '#6B7280';
      ctx.font = '10px sans-serif';
      ctx.save();
      ctx.translate(i * barWidth + barWidth / 2, h - 4);
      ctx.textAlign = 'center';
      ctx.fillText(point.date.slice(5), 0, 0);
      ctx.restore();
    }
  });
}

drawBarChart('chart-registrations', registrationsData, '#2563EB');
drawBarChart('chart-closed', closedData, '#22C55E');
drawBarChart('chart-payments', paymentsData, '#F59E0B');
</script>
<?php
admin_footer();
