<?php
/** @var string $driverStatus */
/** @var string|null $rejectReason */
?>
<div class="container">
  <h1><?= e(t('nav.feed')) ?></h1>

  <?php if ($driverStatus === 'pending'): ?>
    <div class="banner"><?= e(t('auth.driver_pending_banner')) ?></div>
  <?php elseif ($driverStatus === 'rejected'): ?>
    <div class="banner banner-error"><?= e(t('auth.driver_rejected_banner', ['reason' => $rejectReason ?? ''])) ?></div>
  <?php endif; ?>

  <div class="empty-state">
    <p><?= e(t('common.empty_title')) ?></p>
    <p class="text-soft">Tam lent (tablar, SSE, filtrlər) FAZA 2/4-də əlavə olunacaq.</p>
  </div>
</div>
