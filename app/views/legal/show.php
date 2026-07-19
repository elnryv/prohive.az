<?php
/** @var array $doc */
/** @var string $html */
?>
<div class="container">
  <a href="/huquqi" class="text-soft" style="display:inline-flex;align-items:center;gap:4px"><?= icon('chevron-left', 'icon', 16) ?> <?= e(t('legal.title')) ?></a>
  <h1 style="margin-top:8px"><?= e($doc['title_az']) ?></h1>
  <div class="card legal-content">
    <?= $html ?>
  </div>
</div>
