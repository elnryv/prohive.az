<?php
/** @var array $customers */
/** @var string $q */
use App\Core\Phone;
?>
<h1 style="display:flex;align-items:center;gap:8px"><?= icon('user') ?> Müştərilər</h1>

<form method="get" class="admin-toolbar">
  <input type="text" name="q" placeholder="Nömrə və ya ad axtar..." value="<?= e($q) ?>">
  <button type="submit" class="btn btn-sm"><?= icon('search', 'icon', 16) ?></button>
</form>

<?php if ($customers === []): ?>
  <div class="empty-state"><p>Nəticə yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($customers as $c): ?>
    <a class="admin-row" href="/musteriler/<?= (int) $c['id'] ?>">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($c['full_name']) ?></span>
        <?php if ((int) $c['is_blocked'] === 1): ?><span class="chip" style="border-color:var(--danger);color:var(--danger)">bloklu</span><?php endif; ?>
      </div>
      <div class="admin-row-meta">
        <span><?= icon('phone', 'icon', 14) ?> <?= e(Phone::display($c['phone'])) ?></span>
        <span><?= icon('box', 'icon', 14) ?> <?= (int) $c['listing_count'] ?> elan</span>
        <span><?= icon('check', 'icon', 14) ?> <?= (int) $c['accepted_count'] ?> qəbul</span>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
