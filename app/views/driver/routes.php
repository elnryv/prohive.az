<?php
/** @var array $routes */
/** @var array $locations */
/** @var bool $canAddMore */
use App\Core\Csrf;
use App\Core\Lang;
?>
<div class="container">
  <h1><?= e(t('routes.title')) ?></h1>
  <p class="text-soft"><?= e(t('routes.description')) ?></p>

  <?php if ($routes === []): ?>
    <div class="empty-state"><p><?= e(t('common.empty_title')) ?></p></div>
  <?php else: ?>
    <?php foreach ($routes as $r): ?>
      <div class="card" style="display:flex;justify-content:space-between;align-items:center">
        <span>
          <?= $r['from_location_id'] ? e(Lang::field($r, 'from')) : e(t('listing.filter_all')) ?>
          →
          <?= $r['to_location_id'] ? e(Lang::field($r, 'to')) : e(t('listing.filter_all')) ?>
          <span class="chip"><?= e(t('listing.tab_' . ($r['scope'] === 'all' ? 'baku' : $r['scope']))) ?></span>
        </span>
        <form method="post" action="/surucu/marsrutlar/<?= (int) $r['id'] ?>/sil">
          <?= Csrf::field() ?>
          <button type="submit" class="btn btn-outline">✕</button>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($canAddMore): ?>
    <form method="post" action="/surucu/marsrutlar" style="margin-top:16px">
      <?= Csrf::field() ?>
      <div class="field">
        <label><?= e(t('listing.from')) ?></label>
        <select name="from_location_id">
          <option value="0"><?= e(t('listing.filter_all')) ?></option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= (int) $loc['id'] ?>"><?= e(Lang::field($loc, 'name')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label><?= e(t('listing.to')) ?></label>
        <select name="to_location_id">
          <option value="0"><?= e(t('listing.filter_all')) ?></option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= (int) $loc['id'] ?>"><?= e(Lang::field($loc, 'name')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label><?= e(t('listing.category')) ?> / <?= e(t('routes.scope')) ?></label>
        <select name="scope">
          <option value="all"><?= e(t('listing.filter_all')) ?></option>
          <option value="baku"><?= e(t('listing.tab_baku')) ?></option>
          <option value="intercity"><?= e(t('listing.tab_intercity')) ?></option>
        </select>
      </div>
      <button type="submit" class="btn btn-amber btn-block"><?= e(t('routes.add')) ?></button>
    </form>
  <?php else: ?>
    <p class="text-soft"><?= e(t('routes.limit_reached')) ?></p>
  <?php endif; ?>
</div>
