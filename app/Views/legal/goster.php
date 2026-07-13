<?php
/**
 * @var callable $t
 * @var string $dil
 * @var bool $girisEdilib
 * @var string|null $rol
 * @var array $sujetler
 * @var array $secilmis
 * @var string $metn Sənədin trusted/statik HTML məzmunu (config/legal/content/*.php-dən) — istifadəçi girişi deyil, escape edilmir.
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="card glass">
  <p><a href="/huquqi">&larr; <?= htmlspecialchars($t('huquqi.geri')) ?></a></p>
  <h1><?= htmlspecialchars($secilmis['basliq']) ?></h1>

  <?php if ($dil !== 'az'): ?>
    <p style="font-size:13px; color:var(--text-dim);"><?= htmlspecialchars($t('huquqi.dil_qeyd')) ?></p>
  <?php endif; ?>

  <div class="legal-doc">
    <?= $metn ?>
  </div>
</div>
<?php require __DIR__ . '/../partials/foot.php'; ?>
