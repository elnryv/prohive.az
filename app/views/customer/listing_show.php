<?php
/** @var array $listing */
/** @var array $photos */
/** @var array $offers */
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Config;

$statusChip = match ($listing['status']) {
    'active' => ['chip-active', 'listing.status_active'],
    'accepted' => ['chip-ok', 'listing.status_accepted'],
    'completed' => ['chip-muted', 'listing.status_completed'],
    'expired' => ['chip-muted', 'listing.status_expired'],
    'removed' => ['chip-muted', 'listing.status_removed'],
    default => ['chip-muted', 'listing.status_active'],
};
$publicUrl = rtrim((string) Config::get('app.base_url'), '/') . '/e/' . $listing['public_code'];
?>
<div class="container">
  <div class="route">
    <span><?= e(Lang::field($listing, 'from')) ?></span>
    <span class="arrow">→</span>
    <span><?= e(Lang::field($listing, 'to')) ?></span>
  </div>
  <div style="display:flex;gap:8px;margin:8px 0;flex-wrap:wrap">
    <span class="chip <?= $statusChip[0] ?>"><?= e(t($statusChip[1])) ?></span>
    <span class="chip"><?= e($listing['icon']) ?> <?= e(Lang::field($listing, 'cat')) ?></span>
    <?php if ((int) $listing['is_urgent'] === 1): ?><span class="chip chip-urgent">⚡ <?= e(t('listing.urgent')) ?></span><?php endif; ?>
  </div>

  <div class="card">
    <p><?= nl2br(e($listing['description'])) ?></p>
    <?php if (!empty($listing['from_detail']) || !empty($listing['to_detail'])): ?>
      <p class="text-soft" style="font-size:13px">
        <?php if (!empty($listing['from_detail'])): ?><?= e(t('listing.from')) ?>: <?= e($listing['from_detail']) ?><br><?php endif; ?>
        <?php if (!empty($listing['to_detail'])): ?><?= e(t('listing.to')) ?>: <?= e($listing['to_detail']) ?><?php endif; ?>
      </p>
    <?php endif; ?>
    <?php if ($photos !== []): ?>
      <div style="display:flex;gap:6px;overflow-x:auto;margin-top:8px">
        <?php foreach ($photos as $p): ?>
          <img src="/uploads/listings/<?= e($p['filename']) ?>" style="width:88px;height:88px;object-fit:cover;border-radius:8px" loading="lazy">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($listing['status'] === 'active'): ?>
    <div style="display:flex;gap:8px;margin-bottom:16px">
      <form method="post" action="/musteri/elan/<?= (int) $listing['id'] ?>/sil" style="flex:1" onsubmit="return confirm('<?= e(t('listing.delete_confirm')) ?>')">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-outline btn-block"><?= e(t('listing.delete')) ?></button>
      </form>
      <?php if ((int) $listing['extended'] === 0): ?>
      <form method="post" action="/musteri/elan/<?= (int) $listing['id'] ?>/uzat" style="flex:1">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-block"><?= e(t('listing.extend')) ?></button>
      </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <p class="text-soft"><?= e(t('listing.view_public')) ?></p>
    <a href="<?= e($publicUrl) ?>" style="color:var(--amber);word-break:break-all"><?= e($publicUrl) ?></a>
  </div>

  <h2 style="margin-top:24px">💬 <?= e(t('listing.offers_count', ['n' => count($offers)])) ?></h2>
  <?php if ($offers === []): ?>
    <div class="empty-state"><p><?= e(t('listing.offers_none')) ?></p></div>
  <?php else: ?>
    <?php foreach ($offers as $offer): ?>
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span class="num" style="font-size:22px"><?= number_format((float) $offer['price'], 2) ?> AZN</span>
          <?php if ($offer['status'] === 'accepted'): ?><span class="chip chip-ok"><?= e(t('listing.status_accepted')) ?></span><?php endif; ?>
        </div>
        <?php if (!empty($offer['note'])): ?><p class="text-soft"><?= e($offer['note']) ?></p><?php endif; ?>
        <p class="text-soft" style="font-size:13px">
          <?= e($offer['full_name']) ?> · <?= e(Lang::field(['name_az' => $offer['vt_name_az'], 'name_ru' => $offer['vt_name_ru'], 'name_en' => $offer['vt_name_en']], 'name')) ?>
          · ✓ <?= (int) $offer['jobs_done'] ?> iş
          <?php if ((int) $offer['cancel_count'] > 0): ?> · ⚠ <?= (int) $offer['cancel_count'] ?> ləğv<?php endif; ?>
        </p>

        <?php if ($offer['status'] === 'pending' && $listing['status'] === 'active'): ?>
          <form method="post" action="/musteri/elan/<?= (int) $listing['id'] ?>/teklif/<?= (int) $offer['id'] ?>/qebul" style="margin-top:8px" onsubmit="return confirm('<?= e(t('offer.accept_confirm')) ?>')">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-amber btn-block"><?= e(t('listing.accept')) ?></button>
          </form>
        <?php endif; ?>
        <!-- Nömrə açılışı (Q-Y3/Q-Y4) FAZA 3-də əlavə olunur: yalnız accepted_offer_id === bu təklif
             VƏ giriş edən istifadəçi customer_id/accepted_driver_id-dən biridirsə göstərilir. -->
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
