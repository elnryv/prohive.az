<?php
/**
 * @var callable $t
 * @var string $dil
 * @var bool $girisEdilib
 * @var string|null $rol
 * @var string|null $baslik
 */
$hazirkiYol = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/';
?><!doctype html>
<html lang="<?= htmlspecialchars($dil) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= htmlspecialchars($baslik ?? $t('ortaq.app_adi')) ?></title>
<meta name="theme-color" content="#f5f3fb">
<link rel="manifest" href="/manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Birlikdə">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet" href="/assets/css/app.css">
<script src="/assets/js/app.js"></script>
</head>
<body>

<div id="splashOverlay">
  <div class="splash-blob" style="width:64px;height:64px;background:var(--accent);"></div>
  <div class="splash-blob" style="width:46px;height:46px;background:var(--accent-warm);"></div>
  <div class="splash-blob" style="width:38px;height:38px;background:var(--pink);"></div>
  <div class="splash-blob" style="width:30px;height:30px;background:var(--warning);"></div>
  <div class="splash-blob" style="width:52px;height:52px;background:var(--success);"></div>
  <div class="splash-center">
    <div class="splash-word"><?= htmlspecialchars($t('ortaq.app_adi')) ?></div>
    <div class="splash-tag"><?= htmlspecialchars($t('splash.tagline')) ?></div>
  </div>
</div>
<script>
  // Tətbiq DAXİLİNDƏ bir səhifədən digərinə keçid (drawer linki, giriş →
  // panel yönləndirməsi və s.) animasiyanı YENİDƏN göstərməməlidir — yalnız
  // PWA-nı təzə açanda / saytа kənardan (yeni) girəndə göstərilməlidir.
  // document.referrer eyni origin-dədirsə bu, "tətbiq daxili keçid" deməkdir
  // (PWA standalone açılışında və kənar keçiddə referrer boş/fərqli olur).
  (function () {
    var ref = document.referrer;
    var daxiliKecid = false;
    if (ref) {
      try {
        daxiliKecid = new URL(ref).origin === window.location.origin;
      } catch (e) {
        daxiliKecid = false;
      }
    }
    if (daxiliKecid) {
      document.getElementById('splashOverlay').classList.add('hide');
    }
  })();
</script>

<div class="top-nav glass">
  <button class="burger" id="drawerBurger" type="button" aria-label="Menyu"><span></span><span></span><span></span></button>
  <span class="brand"><?= htmlspecialchars($t('ortaq.app_adi')) ?></span>
  <span style="width:40px;"></span>
</div>

<div class="drawer-scrim" id="drawerScrim"></div>
<nav class="drawer" id="drawerNav">
  <div class="drawer-brand"><?= htmlspecialchars($t('ortaq.app_adi')) ?></div>
  <a class="drawer-item drawer-mainsite" href="https://birlikde.biz"><span class="drawer-dot"></span><?= htmlspecialchars($t('drawer.esas_sayt')) ?></a>

  <?php if (!empty($girisEdilib)): ?>
    <?php if (($rol ?? null) === 'musteri'): ?>
      <a class="drawer-item<?= $hazirkiYol === '/panel' ? ' active' : '' ?>" href="/panel"><span class="drawer-dot"></span><?= htmlspecialchars($t('ortaq.panel')) ?></a>
    <?php elseif (in_array($rol ?? null, ['kurye', 'yukdasima'], true)): ?>
      <a class="drawer-item<?= $hazirkiYol === '/lovhe' ? ' active' : '' ?>" href="/lovhe"><span class="drawer-dot"></span><?= htmlspecialchars($t('kurye.lovhe')) ?></a>
      <a class="drawer-item<?= $hazirkiYol === '/profil' ? ' active' : '' ?>" href="/profil"><span class="drawer-dot"></span><?= htmlspecialchars($t('profil.basliq')) ?></a>
    <?php endif; ?>
  <?php else: ?>
    <a class="drawer-item<?= $hazirkiYol === '/giris' ? ' active' : '' ?>" href="/giris"><span class="drawer-dot"></span><?= htmlspecialchars($t('ortaq.giris')) ?></a>
    <a class="drawer-item<?= $hazirkiYol === '/qeydiyyat' ? ' active' : '' ?>" href="/qeydiyyat"><span class="drawer-dot"></span><?= htmlspecialchars($t('ortaq.qeydiyyat')) ?></a>
  <?php endif; ?>

  <div class="drawer-section-label"><?= htmlspecialchars($t('drawer.diger')) ?></div>
  <a class="drawer-item<?= $hazirkiYol === '/huquqi' || str_starts_with($hazirkiYol, '/huquqi/') ? ' active' : '' ?>" href="/huquqi"><span class="drawer-dot"></span><?= htmlspecialchars($t('huquqi.basliq')) ?></a>

  <div class="drawer-langs">
    <a class="lang-flag<?= $dil === 'az' ? ' active' : '' ?>" href="?dil=az" title="Azərbaycan" aria-label="Azərbaycan" onclick="event.preventDefault(); Birlikde.switchLanguage('az');">&#127462;&#127487;</a>
    <a class="lang-flag<?= $dil === 'ru' ? ' active' : '' ?>" href="?dil=ru" title="Русский" aria-label="Русский" onclick="event.preventDefault(); Birlikde.switchLanguage('ru');">&#127479;&#127482;</a>
    <a class="lang-flag<?= $dil === 'en' ? ' active' : '' ?>" href="?dil=en" title="English" aria-label="English" onclick="event.preventDefault(); Birlikde.switchLanguage('en');">&#127468;&#127463;</a>
  </div>

  <?php if (!empty($girisEdilib)): ?>
    <div class="drawer-exit" id="navCixisBtn"><?= htmlspecialchars($t('ortaq.cixis')) ?></div>
  <?php endif; ?>
</nav>

<div class="container">
