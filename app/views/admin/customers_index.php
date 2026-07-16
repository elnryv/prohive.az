<?php
/** @var array $customers */
/** @var string $q */
use App\Core\Phone;
?>
<h1>Müştərilər</h1>

<form method="get" class="admin-toolbar">
  <input type="text" name="q" placeholder="Nömrə və ya ad axtar..." value="<?= e($q) ?>">
  <button type="submit" class="btn btn-sm">Axtar</button>
</form>

<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Ad</th><th>Nömrə</th><th>Elan sayı</th><th>Qəbul sayı</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($customers as $c): ?>
    <tr>
      <td><?= e($c['full_name']) ?> <?php if ((int) $c['is_blocked'] === 1): ?><span class="chip" style="border-color:var(--danger);color:var(--danger)">bloklu</span><?php endif; ?></td>
      <td><?= e(Phone::display($c['phone'])) ?></td>
      <td><?= (int) $c['listing_count'] ?></td>
      <td><?= (int) $c['accepted_count'] ?></td>
      <td><a href="/musteriler/<?= (int) $c['id'] ?>" class="btn btn-sm">Bax</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if ($customers === []): ?><tr><td colspan="5" class="text-soft">Nəticə yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
