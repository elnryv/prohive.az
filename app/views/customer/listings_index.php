<?php
/** @var array $listings */
/** @var bool $justCreated */
use App\Core\View;
?>
<div class="container">
  <?php if ($justCreated): ?>
    <div class="banner" style="border-left-color:var(--ok)"><?= e(t('listing.created_ok')) ?></div>
  <?php endif; ?>
  <h1><?= e(t('nav.my_listings')) ?></h1>

  <?php if ($listings === []): ?>
    <div class="empty-state">
      <p><?= e(t('listing.no_listings')) ?></p>
      <a class="btn btn-amber" href="/musteri/elan/yeni"><?= e(t('nav.new_listing')) ?></a>
    </div>
  <?php else: ?>
    <?php foreach ($listings as $listing): ?>
      <?php View::partial('partials/listing_card', ['listing' => $listing, 'href' => '/musteri/elan/' . $listing['id']]); ?>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
