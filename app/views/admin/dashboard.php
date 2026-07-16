<?php
/** @var int $todayListings */
/** @var int $activeListings */
/** @var int $todayAccepted */
/** @var int $pendingDrivers */
/** @var array $billingBreakdown */
/** @var float $mrr */
/** @var array $routeHeat */
$billingMap = [];
foreach ($billingBreakdown as $row) {
    $billingMap[$row['billing_status'] ?? 'none'] = (int) $row['c'];
}
?>
<h1>Dashboard</h1>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= $todayListings ?></div><div class="label">Bugünkü elan</div></div>
  <div class="stat-card"><div class="num"><?= $activeListings ?></div><div class="label">Aktiv elan</div></div>
  <div class="stat-card"><div class="num"><?= $todayAccepted ?></div><div class="label">Bugünkü qəbul</div></div>
  <div class="stat-card"><div class="num"><?= $pendingDrivers ?></div><div class="label">Təsdiq gözləyən sürücü</div>
    <?php if ($pendingDrivers > 0): ?><a href="/surucular?tab=pending" style="color:var(--amber);font-size:12px">Bax →</a><?php endif; ?>
  </div>
  <div class="stat-card"><div class="num"><?= number_format($mrr, 0) ?> AZN</div><div class="label">MRR (bu ay)</div></div>
  <div class="stat-card">
    <div class="num" style="font-size:16px">
      🟢<?= $billingMap['paid'] ?? 0 ?> · 🟡<?= $billingMap['trial'] ?? 0 ?> · ⚪<?= $billingMap['free'] ?? 0 ?>
    </div>
    <div class="label">Paid · Trial · Free</div>
  </div>
</div>

<h2>Marşrut istiliyi (son 30 gün)</h2>
<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Marşrut</th><th>Elan sayı</th><th>Təklif sayı</th><th>Qəbul faizi</th></tr></thead>
  <tbody>
  <?php foreach ($routeHeat as $r): ?>
    <?php $pct = $r['listing_count'] > 0 ? round(($r['accepted_count'] / $r['listing_count']) * 100) : 0; ?>
    <tr>
      <td><?= e($r['from_name']) ?> → <?= e($r['to_name']) ?></td>
      <td><?= (int) $r['listing_count'] ?></td>
      <td><?= (int) $r['offer_count'] ?></td>
      <td><?= $pct ?>%</td>
    </tr>
  <?php endforeach; ?>
  <?php if ($routeHeat === []): ?><tr><td colspan="4" class="text-soft">Məlumat yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
