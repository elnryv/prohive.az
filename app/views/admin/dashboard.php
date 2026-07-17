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
  <div class="stat-card">
    <div class="num"><?= $pendingDrivers ?></div><div class="label">Təsdiq gözləyən sürücü</div>
    <?php if ($pendingDrivers > 0): ?><a href="/surucular?tab=pending" style="color:var(--primary);font-size:12px">Bax →</a><?php endif; ?>
  </div>
  <div class="stat-card"><div class="num"><?= number_format($mrr, 0) ?> AZN</div><div class="label">MRR (bu ay)</div></div>
  <div class="stat-card">
    <div class="num" style="font-size:15px;display:flex;gap:10px">
      <span style="color:var(--ok)"><?= $billingMap['paid'] ?? 0 ?></span>
      <span style="color:var(--primary)"><?= $billingMap['trial'] ?? 0 ?></span>
      <span style="color:var(--txt-soft)"><?= $billingMap['free'] ?? 0 ?></span>
    </div>
    <div class="label">Paid · Trial · Free</div>
  </div>
</div>

<h2 style="display:flex;align-items:center;gap:6px"><?= icon('map-pin') ?> Marşrut istiliyi (son 30 gün)</h2>
<?php if ($routeHeat === []): ?>
  <div class="empty-state"><p>Məlumat yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($routeHeat as $r): ?>
    <?php $pct = $r['listing_count'] > 0 ? round(($r['accepted_count'] / $r['listing_count']) * 100) : 0; ?>
    <div class="admin-row">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($r['from_name']) ?> → <?= e($r['to_name']) ?></span>
        <span class="chip <?= $pct >= 50 ? 'chip-ok' : 'chip-muted' ?>"><?= $pct ?>%</span>
      </div>
      <div class="admin-row-meta">
        <span><?= icon('box', 'icon', 14) ?> <?= (int) $r['listing_count'] ?> elan</span>
        <span><?= icon('message', 'icon', 14) ?> <?= (int) $r['offer_count'] ?> təklif</span>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
