<?php
/** @var array $drivers */
/** @var string $tab */
/** @var string $q */
use App\Core\Phone;

$statusChipClass = static fn (string $s) => match ($s) {
    'approved' => 'chip-ok',
    'pending' => 'chip-warn',
    default => 'chip-muted',
};
?>
<h1 style="display:flex;align-items:center;gap:8px"><?= icon('truck') ?> Sürücülər</h1>

<div class="tabs" style="max-width:300px">
  <a href="/surucular" class="<?= $tab === 'all' ? 'active' : '' ?>">Hamısı</a>
  <a href="/surucular?tab=pending" class="<?= $tab === 'pending' ? 'active' : '' ?>">Təsdiq gözləyən</a>
</div>

<form method="get" class="admin-toolbar">
  <input type="hidden" name="tab" value="<?= e($tab) ?>">
  <input type="text" name="q" placeholder="Nömrə və ya ad axtar..." value="<?= e($q) ?>">
  <button type="submit" class="btn btn-sm"><?= icon('search', 'icon', 16) ?></button>
</form>

<?php if ($drivers === []): ?>
  <div class="empty-state"><p>Nəticə yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($drivers as $d): ?>
    <a class="admin-row" href="/surucular/<?= (int) $d['id'] ?>">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($d['full_name']) ?></span>
        <div style="display:flex;gap:4px">
          <span class="chip <?= $statusChipClass($d['driver_status']) ?>"><?= e($d['driver_status']) ?></span>
          <?php if ((int) $d['is_blocked'] === 1): ?><span class="chip" style="border-color:var(--danger);color:var(--danger)">bloklu</span><?php endif; ?>
        </div>
      </div>
      <div class="admin-row-meta">
        <span><?= icon('phone', 'icon', 14) ?> <?= e(Phone::display($d['phone'])) ?></span>
        <span><?= icon('truck', 'icon', 14) ?> <?= e($d['vt_name'] ?? '—') ?></span>
        <?php if ($d['custom_price'] !== null): ?>
          <span class="chip chip-active"><?= number_format((float) $d['custom_price'], 0) ?> AZN</span>
        <?php else: ?>
          <span><?= icon('wallet', 'icon', 14) ?> <?= e($d['billing_status'] ?? '—') ?></span>
        <?php endif; ?>
        <span><?= icon('check', 'icon', 14) ?> <?= (int) $d['jobs_done'] ?> iş</span>
        <?php if ((int) $d['cancel_count'] > 0): ?><span><?= icon('alert-triangle', 'icon', 14) ?> <?= (int) $d['cancel_count'] ?></span><?php endif; ?>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
