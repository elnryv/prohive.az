<?php
/** @var string $driverStatus */
/** @var string|null $rejectReason */
/** @var bool $isActive */
/** @var array $listings */
/** @var string $scope */
/** @var array $categories */
/** @var array $locations */
/** @var array $filters */
use App\Core\Lang;
use App\Core\View;
?>
<div class="container">
  <?php if ($driverStatus === 'pending'): ?>
    <div class="banner"><?= e(t('auth.driver_pending_banner')) ?></div>
  <?php elseif ($driverStatus === 'rejected'): ?>
    <div class="banner banner-error"><?= e(t('auth.driver_rejected_banner', ['reason' => $rejectReason ?? ''])) ?></div>
  <?php elseif (!$isActive): ?>
    <div class="banner"><?= e(t('listing.driver_inactive_locked')) ?> <a href="/surucu/odenis" style="color:var(--amber)">→</a></div>
  <?php endif; ?>

  <div class="tabs">
    <a href="?tab=baku" class="<?= $scope === 'baku' ? 'active' : '' ?>"><?= e(t('listing.tab_baku')) ?></a>
    <a href="?tab=intercity" class="<?= $scope === 'intercity' ? 'active' : '' ?>"><?= e(t('listing.tab_intercity')) ?></a>
  </div>

  <form method="get" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <input type="hidden" name="tab" value="<?= e($scope) ?>">
    <select name="category_id" onchange="this.form.submit()" style="flex:1;min-width:120px">
      <option value="0"><?= e(t('listing.filter_all')) ?> — <?= e(t('listing.category')) ?></option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>" <?= $filters['categoryId'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['icon']) ?> <?= e(Lang::field($cat, 'name')) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($listings === []): ?>
    <div class="empty-state"><p><?= e(t('common.empty_title')) ?></p></div>
  <?php else: ?>
    <div id="feed-list">
    <?php foreach ($listings as $listing): ?>
      <?php View::partial('partials/listing_card', ['listing' => $listing, 'href' => '/surucu/elan/' . $listing['id']]); ?>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
