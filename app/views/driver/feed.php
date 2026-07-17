<?php
/** @var string $driverStatus */
/** @var string|null $rejectReason */
/** @var bool $isActive */
/** @var array $listings */
/** @var string $scope */
/** @var array $categories */
/** @var array $locations */
/** @var array $filters */
/** @var int $lastEventId */
/** @var array $banners */
/** @var int $todayCount */
/** @var int $activeCount */
use App\Core\Auth;
use App\Core\Lang;
use App\Core\View;

$firstName = explode(' ', trim((string) (Auth::user()['full_name'] ?? '')))[0] ?? '';
?>
<div class="container">
  <?php View::partial('partials/banner_carousel', ['banners' => $banners]); ?>

  <div class="dash-greeting">
    <span class="text-soft"><?= e(t('home.greeting', ['name' => $firstName])) ?></span>
  </div>
  <p class="text-soft" style="margin:-12px 0 16px;font-size:14px"><?= e(t('home.driver_greeting_sub')) ?></p>

  <div class="dash-stats">
    <div class="dash-stat"><div class="num"><?= (int) $todayCount ?></div><div class="label"><?= e(t('home.driver_today_stat')) ?></div></div>
    <div class="dash-stat"><div class="num"><?= (int) $activeCount ?></div><div class="label"><?= e(t('home.driver_active_stat')) ?></div></div>
  </div>

  <?php if ($driverStatus === 'pending'): ?>
    <div class="banner"><?= e(t('auth.driver_pending_banner')) ?></div>
  <?php elseif ($driverStatus === 'rejected'): ?>
    <div class="banner banner-error"><?= e(t('auth.driver_rejected_banner', ['reason' => $rejectReason ?? ''])) ?></div>
  <?php elseif (!$isActive): ?>
    <div class="banner"><?= e(t('listing.driver_inactive_locked')) ?> <a href="/surucu/odenis" style="color:var(--primary)">→</a></div>
  <?php endif; ?>

  <div class="tabs">
    <a href="?tab=baku" class="<?= $scope === 'baku' ? 'active' : '' ?>"><?= e(t('listing.tab_baku')) ?></a>
    <a href="?tab=intercity" class="<?= $scope === 'intercity' ? 'active' : '' ?>"><?= e(t('listing.tab_intercity')) ?></a>
  </div>

  <form method="get" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <input type="hidden" name="tab" value="<?= e($scope) ?>">
    <select name="category_id" onchange="this.form.submit()" style="flex:1;min-width:120px" aria-label="<?= e(t('listing.category')) ?>">
      <option value="0"><?= e(t('listing.filter_all')) ?> — <?= e(t('listing.category')) ?></option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>" <?= $filters['categoryId'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['icon']) ?> <?= e(Lang::field($cat, 'name')) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <div id="feed-list" data-last-event-id="<?= (int) $lastEventId ?>">
  <?php if ($listings === []): ?>
    <div class="empty-state" data-empty-placeholder><span class="empty-state-icon"><?= icon('box', 'icon', 28) ?></span><p><?= e(t('common.empty_title')) ?></p></div>
  <?php else: ?>
    <?php foreach ($listings as $listing): ?>
      <?php View::partial('partials/listing_card', ['listing' => $listing, 'href' => '/surucu/elan/' . $listing['id']]); ?>
    <?php endforeach; ?>
  <?php endif; ?>
  </div>
</div>
