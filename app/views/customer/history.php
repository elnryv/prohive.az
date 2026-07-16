<?php
/** @var array $listings */
use App\Core\View;
?>
<div class="container">
  <h1><?= e(t('nav.history')) ?></h1>

  <?php if ($listings === []): ?>
    <div class="empty-state"><p><?= e(t('listing.no_listings')) ?></p></div>
  <?php else: ?>
    <?php foreach ($listings as $listing): ?>
      <?php View::partial('partials/listing_card', ['listing' => $listing, 'href' => '/musteri/elan/' . $listing['id']]); ?>
      <a class="btn btn-outline btn-block" style="margin:-4px 0 12px" href="/musteri/tarixce/<?= (int) $listing['id'] ?>/yenidenSifaris"><?= e(t('listing.new_order')) ?></a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
