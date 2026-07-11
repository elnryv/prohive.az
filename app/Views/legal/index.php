<?php
/**
 * @var callable $t
 * @var string $dil
 * @var bool $girisEdilib
 * @var string|null $rol
 * @var array $sujetler
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="card glass">
  <h1><?= htmlspecialchars($t('huquqi.basliq')) ?></h1>

  <div class="legal-callout legal-callout-warn">
    <strong>⚠ <?= htmlspecialchars($t('huquqi.qaralama_basliq')) ?></strong>
    <p style="margin:6px 0 0;"><?= htmlspecialchars($t('huquqi.qaralama_metn')) ?></p>
  </div>

  <?php if ($dil !== 'az'): ?>
    <p style="font-size:13px; color:var(--text-dim);"><?= htmlspecialchars($t('huquqi.dil_qeyd')) ?></p>
  <?php endif; ?>

  <ul class="legal-list">
    <?php foreach ($sujetler as $sujet): ?>
      <li>
        <a href="/huquqi/<?= htmlspecialchars($sujet['slug']) ?>"><?= htmlspecialchars($sujet['basliq']) ?></a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php require __DIR__ . '/../partials/foot.php'; ?>
