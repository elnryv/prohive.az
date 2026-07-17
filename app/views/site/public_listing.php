<?php
/** @var array $listing */
/** @var int $offersCount */
/** @var array $photos */
/** @var bool $isLoggedIn */
use App\Core\Icon;
use App\Core\Lang;
?>
<div class="container">
  <div class="route">
    <span><?= e(Lang::field($listing, 'from')) ?></span>
    <span class="arrow">→</span>
    <span><?= e(Lang::field($listing, 'to')) ?></span>
  </div>

  <div style="display:flex;gap:8px;margin:8px 0;flex-wrap:wrap">
    <span class="chip"><?= icon(Icon::forCategorySlug($listing['category_slug'] ?? null), 'icon', 14) ?> <?= e(Lang::field($listing, 'cat')) ?></span>
    <?php if ((int) $listing['is_urgent'] === 1): ?><span class="chip chip-urgent"><?= icon('zap', 'icon', 14) ?> <?= e(t('listing.urgent')) ?></span><?php endif; ?>
    <span class="chip"><?= $listing['move_date'] ? e($listing['move_date']) : e(t('common.agreement')) ?></span>
  </div>

  <div class="card">
    <p><?= nl2br(e($listing['description'])) ?></p>
    <?php if ($photos !== []): ?>
      <div style="display:flex;gap:6px;overflow-x:auto;margin-top:8px">
        <?php foreach ($photos as $p): ?>
          <img src="/uploads/listings/<?= e($p['filename']) ?>" style="width:96px;height:96px;object-fit:cover;border-radius:8px" loading="lazy">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <p class="chip chip-warn" style="display:inline-flex"><?= e(t('listing.offer_count_on_public', ['n' => $offersCount])) ?></p>

  <?php if (!$isLoggedIn): ?>
    <div style="margin-top:24px;display:grid;gap:8px">
      <a class="btn btn-amber btn-block" href="/qeydiyyat?rol=surucu"><?= e(t('listing.cta_driver_offer')) ?></a>
      <a class="btn btn-outline btn-block" href="/qeydiyyat?rol=musteri"><?= e(t('listing.cta_customer_own')) ?></a>
    </div>
  <?php endif; ?>
</div>
