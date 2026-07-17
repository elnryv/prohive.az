<?php
/** @var array $listings */
/** @var array $reports */
/** @var string $status */
/** @var string $scope */
use App\Core\Csrf;
use App\Core\Lang;

$statusChipClass = static fn (string $s) => match ($s) {
    'active' => 'chip-active',
    'accepted' => 'chip-ok',
    default => 'chip-muted',
};
?>
<h1 style="display:flex;align-items:center;gap:8px"><?= icon('box') ?> Elanlar</h1>

<?php if ($reports !== []): ?>
<div class="card" style="border-color:var(--warn)">
  <h2 style="margin-top:0;display:flex;align-items:center;gap:6px"><?= icon('alert-triangle') ?> Şikayət növbəsi (<?= count($reports) ?>)</h2>
  <?php foreach ($reports as $r): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--line);gap:8px;flex-wrap:wrap">
      <span><a href="/elanlar/<?= (int) $r['listing_id'] ?>" style="color:var(--primary)"><?= e($r['public_code']) ?></a> — <?= e($r['reason']) ?> (<?= e($r['reporter_name']) ?>)</span>
      <div style="display:flex;gap:6px">
        <form method="post" action="/elanlar/sikayet/<?= (int) $r['id'] ?>"><?= Csrf::field() ?><input type="hidden" name="action" value="resolve"><button class="btn btn-sm btn-amber">Həll edildi</button></form>
        <form method="post" action="/elanlar/sikayet/<?= (int) $r['id'] ?>"><?= Csrf::field() ?><input type="hidden" name="action" value="dismiss"><button class="btn btn-sm btn-outline">Rədd et</button></form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="get" class="admin-toolbar">
  <select name="status" onchange="this.form.submit()" aria-label="Status filtri">
    <option value="">Bütün statuslar</option>
    <?php foreach (['active','accepted','completed','expired','removed'] as $s): ?>
      <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <select name="scope" onchange="this.form.submit()" aria-label="Əhatə dairəsi filtri">
    <option value="">Bütün scope-lar</option>
    <option value="baku" <?= $scope === 'baku' ? 'selected' : '' ?>>Bakı</option>
    <option value="intercity" <?= $scope === 'intercity' ? 'selected' : '' ?>>Bölgələrarası</option>
  </select>
</form>

<?php if ($listings === []): ?>
  <div class="empty-state"><p>Nəticə yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($listings as $l): ?>
    <a class="admin-row" href="/elanlar/<?= (int) $l['id'] ?>">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e(Lang::field($l, 'from')) ?> → <?= e(Lang::field($l, 'to')) ?></span>
        <span class="chip <?= $statusChipClass($l['status']) ?>"><?= e($l['status']) ?></span>
      </div>
      <div class="admin-row-meta">
        <span><?= icon('list', 'icon', 14) ?> <?= e($l['public_code']) ?></span>
        <span><?= icon('user', 'icon', 14) ?> <?= e($l['customer_name']) ?></span>
        <span><?= icon('message', 'icon', 14) ?> <?= (int) $l['offers_count'] ?></span>
        <span><?= icon('calendar', 'icon', 14) ?> <?= e(substr($l['created_at'], 0, 10)) ?></span>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
