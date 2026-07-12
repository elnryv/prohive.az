<?php
/**
 * @var callable $t
 * @var string $dil
 * @var bool $girisEdilib
 * @var string|null $rol
 * @var string|null $baslik
 */
?><!doctype html>
<html lang="<?= htmlspecialchars($dil) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= htmlspecialchars($baslik ?? $t('ortaq.app_adi')) ?></title>
<meta name="theme-color" content="#000000">
<link rel="manifest" href="/manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="Birlikdə">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet" href="/assets/css/app.css">
<script src="/assets/js/app.js"></script>
</head>
<body>
<div class="top-nav glass">
  <span class="brand"><?= htmlspecialchars($t('ortaq.app_adi')) ?></span>
  <div style="display:flex; align-items:center; gap:12px; font-size:13px;">
    <span class="lang-switch">
      <a class="lang-flag<?= $dil === 'az' ? ' active' : '' ?>" href="?dil=az" title="Azərbaycan" aria-label="Azərbaycan" onclick="event.preventDefault(); Birlikde.switchLanguage('az');">&#127462;&#127487;</a>
      <a class="lang-flag<?= $dil === 'ru' ? ' active' : '' ?>" href="?dil=ru" title="Русский" aria-label="Русский" onclick="event.preventDefault(); Birlikde.switchLanguage('ru');">&#127479;&#127482;</a>
      <a class="lang-flag<?= $dil === 'en' ? ' active' : '' ?>" href="?dil=en" title="English" aria-label="English" onclick="event.preventDefault(); Birlikde.switchLanguage('en');">&#127468;&#127463;</a>
    </span>
    <?php if (!empty($girisEdilib)): ?>
      <?php if (($rol ?? null) === 'musteri'): ?>
        <a href="/panel"><?= htmlspecialchars($t('ortaq.panel')) ?></a>
      <?php elseif (in_array($rol ?? null, ['kurye', 'yukdasima'], true)): ?>
        <a href="/lovhe"><?= htmlspecialchars($t('kurye.lovhe')) ?></a>
        <a href="/profil"><?= htmlspecialchars($t('profil.basliq')) ?></a>
      <?php endif; ?>
      <a href="#" id="navCixisBtn"><?= htmlspecialchars($t('ortaq.cixis')) ?></a>
    <?php else: ?>
      <a href="/giris"><?= htmlspecialchars($t('ortaq.giris')) ?></a>
      <a href="/qeydiyyat"><?= htmlspecialchars($t('ortaq.qeydiyyat')) ?></a>
    <?php endif; ?>
  </div>
</div>
<div class="container">
