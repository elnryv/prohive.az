<?php
/** @var array $documents */
?>
<div class="container">
  <h1><?= e(t('legal.title')) ?></h1>
  <div class="admin-list">
    <?php foreach ($documents as $doc): ?>
      <a class="admin-row" href="/huquqi/<?= e($doc['slug']) ?>">
        <div class="admin-row-top">
          <span class="admin-row-title"><?= e($doc['title_az']) ?></span>
          <?= icon('chevron-right', 'icon', 16) ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>
