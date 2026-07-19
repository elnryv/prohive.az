<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/dashboard-stats.php';

$session = require_admin();
$stats = dashboard_stats(db());

admin_header('Dashboard', $session);
?>
<div class="stat-grid" id="stat-grid">
  <div class="stat-card"><div class="label">Ümumi müştəri sayı</div><div class="value" data-key="total_customers"><?= $stats['total_customers'] ?></div></div>
  <div class="stat-card"><div class="label">Ümumi sürücü sayı</div><div class="value" data-key="total_drivers"><?= $stats['total_drivers'] ?></div></div>
  <div class="stat-card"><div class="label">Aktiv müştərilər</div><div class="value" data-key="active_customers"><?= $stats['active_customers'] ?></div></div>
  <div class="stat-card"><div class="label">Aktiv sürücülər</div><div class="value" data-key="active_drivers"><?= $stats['active_drivers'] ?></div></div>
  <div class="stat-card"><div class="label">Bugünkü yeni qeydiyyatlar</div><div class="value" data-key="today_registrations"><?= $stats['today_registrations'] ?></div></div>
  <div class="stat-card"><div class="label">Aktiv elanlar</div><div class="value" data-key="active_orders"><?= $stats['active_orders'] ?></div></div>
  <div class="stat-card"><div class="label">Bu gün bağlanan elanlar</div><div class="value" data-key="closed_today"><?= $stats['closed_today'] ?></div></div>
  <div class="stat-card"><div class="label">Gözləyən şikayətlər</div><div class="value" data-key="pending_complaints"><?= $stats['pending_complaints'] ?></div></div>
  <div class="stat-card"><div class="label">Aktiv abunələr</div><div class="value" data-key="active_subscriptions"><?= $stats['active_subscriptions'] ?></div></div>
  <div class="stat-card"><div class="label">Bitmək üzrə abunələr</div><div class="value" data-key="expiring_subscriptions"><?= $stats['expiring_subscriptions'] ?></div></div>
  <div class="stat-card"><div class="label">Bugünkü Payriff ödənişləri</div><div class="value"><span data-key="today_payments_count"><?= $stats['today_payments_count'] ?></span> / <span data-key="today_payments_amount"><?= number_format($stats['today_payments_amount'], 2) ?></span> AZN</div></div>
  <div class="stat-card"><div class="label">Disk (boş)</div><div class="value"><span data-key="disk_free_gb"><?= $stats['disk_free_gb'] ?></span> GB</div></div>
</div>

<div class="card">
  <h2>Sistem vəziyyəti</h2>
  <p class="small-text">SSE hadisə axını: <span id="sse-health"><?= $stats['last_event_age_seconds'] === null ? 'hadisə yoxdur' : $stats['last_event_age_seconds'] . ' saniyə əvvəl son hadisə' ?></span></p>
  <p class="small-text" style="color:var(--text-muted);">Statistika hər 5 saniyədə avtomatik yenilənir.</p>
</div>

<script>
async function refreshStats() {
  try {
    const res = await fetch('dashboard-stats.php');
    const data = await res.json();
    for (const [key, value] of Object.entries(data)) {
      const el = document.querySelector(`[data-key="${key}"]`);
      if (!el) continue;
      if (key === 'today_payments_amount') el.textContent = Number(value).toFixed(2);
      else el.textContent = value;
    }
    const sseEl = document.getElementById('sse-health');
    if (sseEl) sseEl.textContent = data.last_event_age_seconds === null ? 'hadisə yoxdur' : data.last_event_age_seconds + ' saniyə əvvəl son hadisə';
  } catch {
    // sakitcə keç
  }
}
setInterval(refreshStats, 5000);
</script>
<?php
admin_footer();
