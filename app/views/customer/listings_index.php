<?php
/** @var array $listings */
/** @var bool $justCreated */
use App\Core\Auth;
use App\Core\View;

$firstName = explode(' ', trim((string) (Auth::user()['full_name'] ?? '')))[0] ?? '';
?>
<div class="container">
  <?php if ($justCreated): ?>
    <div class="banner" style="border-left-color:var(--ok)"><?= e(t('listing.created_ok')) ?></div>
  <?php endif; ?>

  <div class="dash-greeting">
    <span class="text-soft"><?= e(t('home.greeting', ['name' => $firstName])) ?></span>
  </div>

  <a class="dash-cta" href="/musteri/elan/yeni">
    <span class="dash-cta-icon"><?= icon('truck', 'icon', 28) ?></span>
    <span>
      <strong><?= e(t('nav.new_listing')) ?></strong>
      <span class="dash-cta-sub"><?= e(t('home.new_listing_sub')) ?></span>
    </span>
    <span class="dash-cta-plus"><?= icon('plus', 'icon', 18) ?></span>
  </a>

  <h2 style="margin-top:24px"><?= e(t('nav.my_listings')) ?></h2>

  <?php if ($listings === []): ?>
    <div class="empty-state">
      <p><?= e(t('listing.no_listings')) ?></p>
    </div>
  <?php else: ?>
    <?php foreach ($listings as $listing): ?>
      <?php View::partial('partials/listing_card', ['listing' => $listing, 'href' => '/musteri/elan/' . $listing['id']]); ?>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
