<?php
/** @var array $offers */
/** @var string $tab */
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Phone;
?>
<div class="container">
  <h1><?= e(t('nav.my_offers')) ?></h1>

  <div class="tabs">
    <a href="?tab=pending" class="<?= $tab === 'pending' ? 'active' : '' ?>"><?= e(t('offer.status_pending')) ?></a>
    <a href="?tab=accepted" class="<?= $tab === 'accepted' ? 'active' : '' ?>"><?= e(t('offer.status_accepted')) ?></a>
    <a href="?tab=lost" class="<?= $tab === 'lost' ? 'active' : '' ?>"><?= e(t('offer.status_lost')) ?></a>
    <a href="?tab=withdrawn" class="<?= $tab === 'withdrawn' ? 'active' : '' ?>"><?= e(t('offer.status_withdrawn')) ?></a>
  </div>

  <?php if ($offers === []): ?>
    <div class="empty-state"><span class="empty-state-icon"><?= icon('message', 'icon', 28) ?></span><p><?= e(t('common.empty_title')) ?></p></div>
  <?php else: ?>
    <?php foreach ($offers as $offer): ?>
      <div class="card">
        <div class="route">
          <span><?= e(Lang::field($offer, 'from')) ?></span>
          <span class="arrow">→</span>
          <span><?= e(Lang::field($offer, 'to')) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
          <span class="num" style="font-size:20px"><?= number_format((float) $offer['price'], 2) ?> AZN</span>
          <span class="chip <?= $offer['offer_status'] === 'accepted' ? 'chip-ok' : 'chip-muted' ?>"><?= e(t('offer.status_' . $offer['offer_status'])) ?></span>
        </div>

        <?php if ($offer['offer_status'] === 'accepted'): ?>
          <div class="card" style="border-color:var(--ok);margin-top:8px">
            <p class="text-soft"><?= e($offer['customer_name']) ?></p>
            <p class="num" style="font-size:22px;color:var(--primary)"><?= e(Phone::display($offer['customer_phone'])) ?></p>
            <div style="display:flex;gap:8px;margin-top:8px">
              <a class="btn btn-amber" style="flex:1" target="_blank" rel="noopener"
                 href="https://wa.me/<?= e($offer['customer_phone']) ?>?text=<?= urlencode(t('whatsapp.template', ['route' => Lang::field($offer, 'from') . ' → ' . Lang::field($offer, 'to')])) ?>">
                WhatsApp
              </a>
              <a class="btn btn-outline" style="flex:1" href="tel:+<?= e($offer['customer_phone']) ?>"><?= e(t('common.call')) ?></a>
            </div>
          </div>
        <?php elseif ($offer['offer_status'] === 'pending'): ?>
          <form method="post" action="/surucu/elan/<?= (int) $offer['id'] ?>/teklif/geri" style="margin-top:8px">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-outline btn-block"><?= e(t('offer.withdraw')) ?></button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
