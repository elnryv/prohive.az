<?php
/** @var array $listing */
/** @var array $photos */
/** @var array $customer */
/** @var bool $isActive */
/** @var string $driverStatus */
/** @var array|null $myOffer */
use App\Core\Csrf;
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

  <div class="card" style="display:flex;align-items:center;gap:10px">
    <?php if (!empty($customer['profile_photo'])): ?>
      <img src="/uploads/profiles/<?= e($customer['profile_photo']) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:50%">
    <?php else: ?>
      <div style="width:44px;height:44px;border-radius:50%;background:var(--card-hi);display:flex;align-items:center;justify-content:center;color:var(--txt-soft)"><?= icon('user', 'icon', 20) ?></div>
    <?php endif; ?>
    <span class="text-soft"><?= e($customer['full_name']) ?></span>
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
    <div class="banner"><?= e(t('listing.driver_inactive_locked')) ?> <a href="/surucu/odenis" style="color:var(--primary)">→</a></div>
  <?php elseif ($myOffer !== null && in_array($myOffer['status'], ['pending', 'accepted'], true)): ?>
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span class="num" style="font-size:22px"><?= number_format((float) $myOffer['price'], 2) ?> AZN</span>
        <span class="chip <?= $myOffer['status'] === 'accepted' ? 'chip-ok' : 'chip-warn' ?>"><?= e(t('offer.status_' . $myOffer['status'])) ?></span>
      </div>
    </div>
    <?php if ($myOffer['status'] === 'pending'): ?>
      <form method="post" action="/surucu/elan/<?= (int) $listing['id'] ?>/teklif" style="margin-top:8px">
        <?= Csrf::field() ?>
        <div class="field">
          <label><?= e(t('offer.price_label')) ?></label>
          <input type="number" name="price" step="0.01" min="0.01" value="<?= e((string) $myOffer['price']) ?>" required>
        </div>
        <div class="field">
          <label><?= e(t('offer.note_label')) ?></label>
          <textarea name="note" maxlength="300"><?= e((string) ($myOffer['note'] ?? '')) ?></textarea>
        </div>
        <button type="submit" class="btn btn-amber btn-block"><?= e(t('offer.update_submit')) ?></button>
      </form>
      <form method="post" action="/surucu/elan/<?= (int) $listing['id'] ?>/teklif/geri" style="margin-top:8px">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-outline btn-block"><?= e(t('offer.withdraw')) ?></button>
      </form>
    <?php endif; ?>
  <?php else: ?>
    <form method="post" action="/surucu/elan/<?= (int) $listing['id'] ?>/teklif">
      <?= Csrf::field() ?>
      <div class="field">
        <label><?= e(t('offer.price_label')) ?></label>
        <input type="number" name="price" step="0.01" min="0.01" required autofocus>
      </div>
      <div class="field">
        <label><?= e(t('offer.note_label')) ?></label>
        <textarea name="note" maxlength="300" placeholder="bu axşam edərəm, 2 fəhlə ilə"></textarea>
      </div>
      <button type="submit" class="btn btn-amber btn-block"><?= e(t('offer.submit')) ?></button>
    </form>
  <?php endif; ?>
</div>
