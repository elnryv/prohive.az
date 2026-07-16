<?php
/** @var array $listing */
/** @var array $photos */
/** @var bool $isActive */
/** @var string $driverStatus */
/** @var array|null $myOffer */
use App\Core\Lang;
?>
<div class="container">
  <div class="route">
    <span><?= e(Lang::field($listing, 'from')) ?></span>
    <span class="arrow">→</span>
    <span><?= e(Lang::field($listing, 'to')) ?></span>
  </div>
  <div style="display:flex;gap:8px;margin:8px 0;flex-wrap:wrap">
    <span class="chip"><?= e($listing['icon']) ?> <?= e(Lang::field($listing, 'cat')) ?></span>
    <?php if ((int) $listing['is_urgent'] === 1): ?><span class="chip chip-urgent">⚡ <?= e(t('listing.urgent')) ?></span><?php endif; ?>
    <span class="chip"><?= $listing['move_date'] ? e($listing['move_date']) : e(t('common.agreement')) ?></span>
  </div>

  <div class="card">
    <p><?= nl2br(e($listing['description'])) ?></p>
    <?php if ($photos !== []): ?>
      <div style="display:flex;gap:6px;overflow-x:auto;margin-top:8px">
        <?php foreach ($photos as $p): ?>
          <img src="/uploads/listings/<?= e($p['filename']) ?>" style="width:88px;height:88px;object-fit:cover;border-radius:8px" loading="lazy">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($listing['status'] !== 'active'): ?>
    <div class="empty-state"><p><?= e(t('listing.status_' . $listing['status'])) ?></p></div>
  <?php elseif ($driverStatus !== 'approved'): ?>
    <div class="banner"><?= e(t('listing.driver_pending_locked')) ?></div>
  <?php elseif (!$isActive): ?>
    <div class="banner"><?= e(t('listing.driver_inactive_locked')) ?> <a href="/surucu/odenis" style="color:var(--amber)">→</a></div>
  <?php elseif ($myOffer !== null): ?>
    <div class="card">
      <span class="num" style="font-size:22px"><?= number_format((float) $myOffer['price'], 2) ?> AZN</span>
      <span class="chip <?= $myOffer['status'] === 'accepted' ? 'chip-ok' : 'chip-warn' ?>"><?= e(t('offer.status_' . $myOffer['status'])) ?></span>
    </div>
  <?php else: ?>
    <!-- Təklif vermə forması FAZA 3-də əlavə olunur (bölmə 7.3). -->
    <div class="card text-soft"><?= e(t('offer.form_coming_soon')) ?></div>
  <?php endif; ?>
</div>
