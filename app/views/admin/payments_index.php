<?php
/** @var array $payments */
/** @var array $monthly */
use App\Core\Phone;
?>
<h1 style="display:flex;align-items:center;gap:8px"><?= icon('wallet') ?> Ödənişlər</h1>

<h2>Aylıq gəlir</h2>
<?php if ($monthly === []): ?>
  <div class="empty-state"><p>Məlumat yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($monthly as $m): ?>
    <div class="admin-row">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($m['ym']) ?></span>
        <span class="chip chip-active"><?= number_format((float) $m['total'], 2) ?> AZN</span>
      </div>
      <div class="admin-row-meta"><span><?= icon('wallet', 'icon', 14) ?> <?= (int) $m['cnt'] ?> ödəniş</span></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<h2 style="margin-top:24px">Bütün ödənişlər</h2>
<?php if ($payments === []): ?>
  <div class="empty-state"><p>Məlumat yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($payments as $p): ?>
    <div class="admin-row">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($p['full_name']) ?></span>
        <span class="chip <?= $p['status'] === 'paid' ? 'chip-ok' : 'chip-muted' ?>"><?= e($p['status']) ?></span>
      </div>
      <div class="admin-row-meta">
        <span><?= icon('phone', 'icon', 14) ?> <?= e(Phone::display($p['phone'])) ?></span>
        <span><?= icon('wallet', 'icon', 14) ?> <?= number_format((float) $p['amount'], 2) ?> <?= e($p['currency']) ?></span>
        <span><?= icon('calendar', 'icon', 14) ?> <?= e(substr($p['created_at'], 0, 16)) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
