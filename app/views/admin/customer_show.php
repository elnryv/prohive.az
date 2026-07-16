<?php
/** @var array $customer */
/** @var array $listings */
use App\Core\Csrf;
use App\Core\Phone;
?>
<a href="/musteriler" class="text-soft">← Müştərilər</a>
<h1><?= e($customer['full_name']) ?></h1>
<p class="text-soft"><?= e(Phone::display($customer['phone'])) ?></p>

<form method="post" action="/musteriler/<?= (int) $customer['id'] ?>/blokla" style="margin-bottom:16px">
  <?= Csrf::field() ?>
  <button type="submit" class="btn btn-outline"><?= (int) $customer['is_blocked'] === 1 ? 'Blokdan çıxar' : 'Blokla' ?></button>
</form>

<h2>Sifariş tarixçəsi</h2>
<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Marşrut</th><th>Kateqoriya</th><th>Status</th><th>Tarix</th></tr></thead>
  <tbody>
  <?php foreach ($listings as $l): ?>
    <tr>
      <td><?= e($l['from_name']) ?> → <?= e($l['to_name']) ?></td>
      <td><?= e($l['icon']) ?> <?= e($l['cat_name']) ?></td>
      <td><?= e($l['status']) ?></td>
      <td><?= e(substr($l['created_at'], 0, 10)) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if ($listings === []): ?><tr><td colspan="4" class="text-soft">Yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
