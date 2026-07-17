<?php
/** @var array $logs */
?>
<h1 style="display:flex;align-items:center;gap:8px"><?= icon('list') ?> Loglar</h1>

<?php if ($logs === []): ?>
  <div class="empty-state"><p>Yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($logs as $l): ?>
    <div class="admin-row">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($l['action']) ?></span>
        <span class="chip chip-muted"><?= e($l['username']) ?></span>
      </div>
      <?php if (!empty($l['details'])): ?><p class="text-soft" style="font-size:12px;margin:4px 0"><?= e((string) $l['details']) ?></p><?php endif; ?>
      <div class="admin-row-meta">
        <?php if (!empty($l['target_type'])): ?><span><?= icon('info', 'icon', 14) ?> <?= e($l['target_type']) ?><?= $l['target_id'] ? ' #' . e((string) $l['target_id']) : '' ?></span><?php endif; ?>
        <span><?= icon('clock', 'icon', 14) ?> <?= e($l['created_at']) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
