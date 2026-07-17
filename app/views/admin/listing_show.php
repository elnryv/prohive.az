<?php
/** @var array $listing */
/** @var array $offers */
/** @var array $events */
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Phone;
?>
<a href="/elanlar" class="text-soft" style="display:inline-flex;align-items:center;gap:4px"><?= icon('chevron-left', 'icon', 16) ?> Elanlar</a>
<h1><?= e(Lang::field($listing, 'from')) ?> → <?= e(Lang::field($listing, 'to')) ?></h1>
<p class="text-soft" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
  <span><?= e($listing['public_code']) ?></span> ·
  <span style="display:inline-flex;align-items:center;gap:4px"><?= icon('user', 'icon', 14) ?> <?= e($listing['customer_name']) ?> (<?= e(Phone::display($listing['customer_phone'])) ?>)</span>
  <span class="chip"><?= e($listing['status']) ?></span>
</p>

<div class="card"><p><?= nl2br(e($listing['description'])) ?></p></div>

<?php if ($listing['status'] !== 'removed'): ?>
<form method="post" action="/elanlar/<?= (int) $listing['id'] ?>/sil" onsubmit="return confirm('Elanı silmək istədiyinə əminsən?')">
  <?= Csrf::field() ?>
  <button type="submit" class="btn btn-outline"><?= icon('trash', 'icon', 16) ?> Elanı sil/gizlət</button>
</form>
<?php endif; ?>

<h2 style="margin-top:20px;display:flex;align-items:center;gap:6px"><?= icon('message', 'icon', 18) ?> Təkliflər</h2>
<?php if ($offers === []): ?>
  <div class="empty-state"><p>Yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($offers as $o): ?>
    <div class="admin-row">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($o['full_name']) ?></span>
        <span class="chip <?= $o['status'] === 'accepted' ? 'chip-ok' : 'chip-muted' ?>"><?= e($o['status']) ?></span>
      </div>
      <div class="admin-row-meta">
        <span><?= icon('phone', 'icon', 14) ?> <?= e(Phone::display($o['phone'])) ?></span>
        <span><?= icon('wallet', 'icon', 14) ?> <?= number_format((float) $o['price'], 2) ?> AZN</span>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<h2 style="margin-top:20px;display:flex;align-items:center;gap:6px"><?= icon('list', 'icon', 18) ?> Hadisə zənciri</h2>
<?php if ($events === []): ?>
  <div class="empty-state"><p>Yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($events as $ev): ?>
    <div class="admin-row">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($ev['event']) ?></span>
        <span class="text-soft" style="font-size:12px"><?= e($ev['created_at']) ?></span>
      </div>
      <?php if (!empty($ev['details'])): ?><p class="text-soft" style="font-size:12px;margin:4px 0 0"><?= e((string) $ev['details']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
