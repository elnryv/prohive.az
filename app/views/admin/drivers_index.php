<?php
/** @var array $drivers */
/** @var string $tab */
/** @var string $q */
use App\Core\Phone;
?>
<h1>Sürücülər</h1>

<div class="tabs" style="max-width:300px">
  <a href="/surucular" class="<?= $tab === 'all' ? 'active' : '' ?>">Hamısı</a>
  <a href="/surucular?tab=pending" class="<?= $tab === 'pending' ? 'active' : '' ?>">Təsdiq gözləyən</a>
</div>

<form method="get" class="admin-toolbar">
  <input type="hidden" name="tab" value="<?= e($tab) ?>">
  <input type="text" name="q" placeholder="Nömrə və ya ad axtar..." value="<?= e($q) ?>">
  <button type="submit" class="btn btn-sm">Axtar</button>
</form>

<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Ad</th><th>Nömrə</th><th>Maşın</th><th>Status</th><th>Billing</th><th>Bitmə</th><th>İş</th><th>Ləğv</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($drivers as $d): ?>
    <tr>
      <td><?= e($d['full_name']) ?></td>
      <td><?= e(Phone::display($d['phone'])) ?></td>
      <td><?= e($d['vt_name'] ?? '—') ?></td>
      <td>
        <span class="chip <?= $d['driver_status'] === 'approved' ? 'chip-ok' : ($d['driver_status'] === 'pending' ? 'chip-warn' : 'chip-muted') ?>">
          <?= e($d['driver_status']) ?>
        </span>
        <?php if ((int) $d['is_blocked'] === 1): ?><span class="chip" style="border-color:var(--danger);color:var(--danger)">bloklu</span><?php endif; ?>
      </td>
      <td>
        <?php if ($d['custom_price'] !== null): ?><span class="chip chip-active"><?= number_format((float) $d['custom_price'], 0) ?> AZN</span>
        <?php else: ?><?= e($d['billing_status'] ?? '—') ?><?php endif; ?>
      </td>
      <td><?= e($d['billing_status'] === 'paid' ? $d['paid_until'] : ($d['billing_status'] === 'trial' ? $d['trial_until'] : '—')) ?></td>
      <td><?= (int) $d['jobs_done'] ?></td>
      <td><?= (int) $d['cancel_count'] ?></td>
      <td><a href="/surucular/<?= (int) $d['id'] ?>" class="btn btn-sm">Bax</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if ($drivers === []): ?><tr><td colspan="9" class="text-soft">Nəticə yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
