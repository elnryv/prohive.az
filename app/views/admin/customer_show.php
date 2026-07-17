<?php
/** @var array $customer */
/** @var array $listings */
use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Phone;
?>
<a href="/musteriler" class="text-soft" style="display:inline-flex;align-items:center;gap:4px"><?= icon('chevron-left', 'icon', 16) ?> Müştərilər</a>

<div style="display:flex;align-items:center;gap:12px;margin:12px 0">
  <?php if (!empty($customer['profile_photo'])): ?>
    <img src="/uploads/profiles/<?= e($customer['profile_photo']) ?>" style="width:56px;height:56px;object-fit:cover;border-radius:50%">
  <?php else: ?>
    <div style="width:56px;height:56px;border-radius:50%;background:var(--card-hi);display:flex;align-items:center;justify-content:center;color:var(--txt-soft)"><?= icon('user', 'icon', 26) ?></div>
  <?php endif; ?>
  <div>
    <h1 style="margin:0"><?= e($customer['full_name']) ?></h1>
    <p class="text-soft" style="margin:2px 0;display:flex;align-items:center;gap:4px"><?= icon('phone', 'icon', 14) ?> <?= e(Phone::display($customer['phone'])) ?></p>
  </div>
</div>

<form method="post" action="/musteriler/<?= (int) $customer['id'] ?>/blokla" style="margin-bottom:16px">
  <?= Csrf::field() ?>
  <button type="submit" class="btn btn-outline"><?= (int) $customer['is_blocked'] === 1 ? 'Blokdan çıxar' : 'Blokla' ?></button>
</form>

<h2 style="display:flex;align-items:center;gap:6px"><?= icon('box', 'icon', 18) ?> Sifariş tarixçəsi</h2>
<?php if ($listings === []): ?>
  <div class="empty-state"><p>Yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($listings as $l): ?>
    <div class="admin-row">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($l['from_name']) ?> → <?= e($l['to_name']) ?></span>
        <span class="chip chip-muted"><?= e($l['status']) ?></span>
      </div>
      <div class="admin-row-meta">
        <span><?= icon(Icon::forCategorySlug($l['category_slug'] ?? null), 'icon', 14) ?> <?= e($l['cat_name']) ?></span>
        <span><?= icon('calendar', 'icon', 14) ?> <?= e(substr($l['created_at'], 0, 10)) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
