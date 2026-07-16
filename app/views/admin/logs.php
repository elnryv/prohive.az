<?php
/** @var array $logs */
?>
<h1>Loglar</h1>

<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Admin</th><th>Əməliyyat</th><th>Hədəf</th><th>Detal</th><th>Tarix</th></tr></thead>
  <tbody>
  <?php foreach ($logs as $l): ?>
    <tr>
      <td><?= e($l['username']) ?></td>
      <td><?= e($l['action']) ?></td>
      <td><?= e(($l['target_type'] ?? '') . ($l['target_id'] ? ' #' . $l['target_id'] : '')) ?></td>
      <td class="text-soft"><?= e((string) ($l['details'] ?? '')) ?></td>
      <td><?= e($l['created_at']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if ($logs === []): ?><tr><td colspan="5" class="text-soft">Yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
