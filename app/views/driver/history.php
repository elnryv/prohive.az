<?php
/** @var array $jobs */
/** @var int $monthlyCount */
/** @var float $monthlyTotal */
/** @var int $cancelCount */
use App\Core\Icon;
use App\Core\Lang;
?>
<div class="container">
  <h1><?= e(t('nav.history')) ?></h1>

  <div class="card" style="border-color:var(--primary)">
    <p class="num" style="font-size:18px"><?= e(t('history.monthly_summary', ['count' => $monthlyCount, 'total' => number_format($monthlyTotal, 2)])) ?></p>
    <?php if ($cancelCount > 0): ?>
      <p class="text-soft" style="display:flex;align-items:center;gap:6px"><?= icon('alert-triangle', 'icon', 14) ?> <?= e(t('history.cancel_count', ['n' => $cancelCount])) ?></p>
    <?php endif; ?>
  </div>

  <?php if ($jobs === []): ?>
    <div class="empty-state"><span class="empty-state-icon"><?= icon('clock', 'icon', 28) ?></span><p><?= e(t('listing.no_listings')) ?></p></div>
  <?php else: ?>
    <?php foreach ($jobs as $job): ?>
      <div class="card">
        <div class="route">
          <span><?= e(Lang::field($job, 'from')) ?></span>
          <span class="arrow">→</span>
          <span><?= e(Lang::field($job, 'to')) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
          <span class="chip"><?= icon(Icon::forCategorySlug($job['category_slug'] ?? null), 'icon', 14) ?> <?= e(Lang::field($job, 'cat')) ?></span>
          <span class="num"><?= number_format((float) $job['price'], 2) ?> AZN</span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
