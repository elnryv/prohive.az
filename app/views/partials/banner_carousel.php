<?php
/** @var array $banners */
use App\Core\Lang;

if ($banners === []) {
    return;
}
?>
<div class="banner-carousel" data-banner-carousel>
  <?php foreach ($banners as $i => $b): ?>
    <?php
      $title = Lang::field($b, 'title');
      $slideInner = '<img src="/uploads/banners/' . e($b['image']) . '" alt="">'
          . ($title !== '' ? '<span class="banner-slide-title">' . e($title) . '</span>' : '');
    ?>
    <?php if (!empty($b['link_url'])): ?>
      <a class="banner-slide <?= $i === 0 ? 'active' : '' ?>" href="<?= e($b['link_url']) ?>" target="_blank" rel="noopener"><?= $slideInner ?></a>
    <?php else: ?>
      <div class="banner-slide <?= $i === 0 ? 'active' : '' ?>"><?= $slideInner ?></div>
    <?php endif; ?>
  <?php endforeach; ?>
  <?php if (count($banners) > 1): ?>
    <div class="banner-dots">
      <?php foreach ($banners as $i => $b): ?>
        <span class="banner-dot <?= $i === 0 ? 'active' : '' ?>"></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
