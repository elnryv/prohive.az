<?php
/** @var array $listings */
/** @var array $reports */
/** @var string $status */
/** @var string $scope */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1>Elanlar</h1>

<?php if ($reports !== []): ?>
<div class="card" style="border-color:var(--warn)">
  <h2 style="margin-top:0">Şikayət növbəsi (<?= count($reports) ?>)</h2>
  <?php foreach ($reports as $r): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--line)">
      <span><a href="/elanlar/<?= (int) $r['listing_id'] ?>" style="color:var(--amber)"><?= e($r['public_code']) ?></a> — <?= e($r['reason']) ?> (<?= e($r['reporter_name']) ?>)</span>
      <div style="display:flex;gap:6px">
        <form method="post" action="/elanlar/sikayet/<?= (int) $r['id'] ?>"><?= Csrf::field() ?><input type="hidden" name="action" value="resolve"><button class="btn btn-sm btn-amber">Həll edildi</button></form>
        <form method="post" action="/elanlar/sikayet/<?= (int) $r['id'] ?>"><?= Csrf::field() ?><input type="hidden" name="action" value="dismiss"><button class="btn btn-sm btn-outline">Rədd et</button></form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="get" class="admin-toolbar">
  <select name="status" onchange="this.form.submit()">
    <option value="">Bütün statuslar</option>
    <?php foreach (['active','accepted','completed','expired','removed'] as $s): ?>
      <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <select name="scope" onchange="this.form.submit()">
    <option value="">Bütün scope-lar</option>
    <option value="baku" <?= $scope === 'baku' ? 'selected' : '' ?>>Bakı</option>
    <option value="intercity" <?= $scope === 'intercity' ? 'selected' : '' ?>>Bölgələrarası</option>
  </select>
</form>

<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Kod</th><th>Marşrut</th><th>Müştəri</th><th>Status</th><th>Təklif</th><th>Tarix</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($listings as $l): ?>
    <tr>
      <td><?= e($l['public_code']) ?></td>
      <td><?= e(Lang::field($l, 'from')) ?> → <?= e(Lang::field($l, 'to')) ?></td>
      <td><?= e($l['customer_name']) ?></td>
      <td><?= e($l['status']) ?></td>
      <td><?= (int) $l['offers_count'] ?></td>
      <td><?= e(substr($l['created_at'], 0, 10)) ?></td>
      <td><a href="/elanlar/<?= (int) $l['id'] ?>" class="btn btn-sm">Bax</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if ($listings === []): ?><tr><td colspan="7" class="text-soft">Nəticə yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
