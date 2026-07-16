<?php
/** @var array $payments */
/** @var array $monthly */
use App\Core\Phone;
?>
<h1>Ödənişlər</h1>

<h2>Aylıq gəlir</h2>
<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Ay</th><th>Cəm</th><th>Say</th></tr></thead>
  <tbody>
  <?php foreach ($monthly as $m): ?>
    <tr><td><?= e($m['ym']) ?></td><td><?= number_format((float) $m['total'], 2) ?> AZN</td><td><?= (int) $m['cnt'] ?></td></tr>
  <?php endforeach; ?>
  <?php if ($monthly === []): ?><tr><td colspan="3" class="text-soft">Məlumat yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<h2>Bütün ödənişlər</h2>
<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Sürücü</th><th>Nömrə</th><th>Məbləğ</th><th>Status</th><th>Tarix</th></tr></thead>
  <tbody>
  <?php foreach ($payments as $p): ?>
    <tr>
      <td><?= e($p['full_name']) ?></td>
      <td><?= e(Phone::display($p['phone'])) ?></td>
      <td><?= number_format((float) $p['amount'], 2) ?> <?= e($p['currency']) ?></td>
      <td><span class="chip <?= $p['status'] === 'paid' ? 'chip-ok' : 'chip-muted' ?>"><?= e($p['status']) ?></span></td>
      <td><?= e(substr($p['created_at'], 0, 16)) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if ($payments === []): ?><tr><td colspan="5" class="text-soft">Məlumat yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
