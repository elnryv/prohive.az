<?php
/** @var array $jobs */
/** @var int $monthlyCount */
/** @var float $monthlyTotal */
/** @var int $cancelCount */
use App\Core\Lang;
?>
<div class="container">
  <h1><?= e(t('nav.history')) ?></h1>

  <div class="card" style="border-color:var(--amber)">
    <p class="num" style="font-size:18px"><?= e(t('history.monthly_summary', ['count' => $monthlyCount, 'total' => number_format($monthlyTotal, 2)])) ?></p>
    <?php if ($cancelCount > 0): ?>
      <p class="text-soft">⚠ <?= e(t('history.cancel_count', ['n' => $cancelCount])) ?></p>
    <?php endif; ?>
  </div>

  <?php if ($jobs === []): ?>
    <div class="empty-state"><p><?= e(t('listing.no_listings')) ?></p></div>
  <?php else: ?>
    <?php foreach ($jobs as $job): ?>
      <div class="card">
        <div class="route">
          <span><?= e(Lang::field($job, 'from')) ?></span>
          <span class="arrow">→</span>
          <span><?= e(Lang::field($job, 'to')) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
          <span class="chip"><?= e($job['icon']) ?> <?= e(Lang::field($job, 'cat')) ?></span>
          <span class="num"><?= number_format((float) $job['price'], 2) ?> AZN</span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
