<?php
/** @var array $listing */
/** @var array $offers */
/** @var array $events */
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Phone;
?>
<a href="/elanlar" class="text-soft">← Elanlar</a>
<h1><?= e(Lang::field($listing, 'from')) ?> → <?= e(Lang::field($listing, 'to')) ?></h1>
<p class="text-soft"><?= e($listing['public_code']) ?> · <?= e($listing['customer_name']) ?> (<?= e(Phone::display($listing['customer_phone'])) ?>) · <span class="chip"><?= e($listing['status']) ?></span></p>

<div class="card"><p><?= nl2br(e($listing['description'])) ?></p></div>

<?php if ($listing['status'] !== 'removed'): ?>
<form method="post" action="/elanlar/<?= (int) $listing['id'] ?>/sil" onsubmit="return confirm('Elanı silmək istədiyinə əminsən?')">
  <?= Csrf::field() ?>
  <button type="submit" class="btn btn-outline">Elanı sil/gizlət</button>
</form>
<?php endif; ?>

<h2>Təkliflər</h2>
<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Sürücü</th><th>Nömrə</th><th>Qiymət</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($offers as $o): ?>
    <tr><td><?= e($o['full_name']) ?></td><td><?= e(Phone::display($o['phone'])) ?></td><td><?= number_format((float) $o['price'], 2) ?> AZN</td><td><?= e($o['status']) ?></td></tr>
  <?php endforeach; ?>
  <?php if ($offers === []): ?><tr><td colspan="4" class="text-soft">Yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<h2>Hadisə zənciri</h2>
<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Hadisə</th><th>Tarix</th><th>Detal</th></tr></thead>
  <tbody>
  <?php foreach ($events as $e): ?>
    <tr><td><?= e($e['event']) ?></td><td><?= e($e['created_at']) ?></td><td class="text-soft"><?= e((string) $e['details']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
