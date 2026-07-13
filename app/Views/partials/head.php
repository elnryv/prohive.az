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
<meta name="theme-color" content="#f5f5fc">
<link rel="manifest" href="/manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Birlikdə">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet" href="<?= \App\Core\Asset::v('css/app.css') ?>">
<script src="<?= \App\Core\Asset::v('js/app.js') ?>"></script>
</head>
<body>

<div id="splashOverlay">
  <div class="splash-center">
    <div class="splash-glow"></div>
    <div class="splash-word"><?php require __DIR__ . '/logo.php'; ?></div>
    <div class="splash-tag"><?= htmlspecialchars($t('splash.tagline')) ?></div>
  </div>
  <div class="splash-loadbar"></div>
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
  <span class="brand"><?php require __DIR__ . '/logo.php'; ?></span>
  <span style="width:40px;"></span>
</div>

<div class="drawer-scrim" id="drawerScrim"></div>
<nav class="drawer" id="drawerNav">
  <div class="drawer-brand"><?php require __DIR__ . '/logo.php'; ?></div>
  <div class="drawer-langs">
    <a class="lang-flag<?= $dil === 'az' ? ' active' : '' ?>" href="?dil=az" title="Azərbaycan" aria-label="Azərbaycan" onclick="event.preventDefault(); Birlikde.switchLanguage('az');">&#127462;&#127487;</a>
    <a class="lang-flag<?= $dil === 'ru' ? ' active' : '' ?>" href="?dil=ru" title="Русский" aria-label="Русский" onclick="event.preventDefault(); Birlikde.switchLanguage('ru');">&#127479;&#127482;</a>
    <a class="lang-flag<?= $dil === 'en' ? ' active' : '' ?>" href="?dil=en" title="English" aria-label="English" onclick="event.preventDefault(); Birlikde.switchLanguage('en');">&#127468;&#127463;</a>
  </div>

  <?php if (empty($girisEdilib)): ?>
    <a class="drawer-item<?= $hazirkiYol === '/giris' ? ' active' : '' ?>" href="/giris"><span class="drawer-dot"></span><?= htmlspecialchars($t('ortaq.giris')) ?></a>
  <?php endif; ?>

  <div class="drawer-bottom">
    <a class="drawer-item" href="https://birlikde.biz"><span class="drawer-dot"></span><?= htmlspecialchars($t('drawer.esas_sayt')) ?></a>
    <a class="drawer-item<?= $hazirkiYol === '/huquqi' || str_starts_with($hazirkiYol, '/huquqi/') ? ' active' : '' ?>" href="/huquqi"><span class="drawer-dot"></span><?= htmlspecialchars($t('huquqi.basliq')) ?></a>
    <?php if (!empty($girisEdilib)): ?>
      <div class="drawer-exit" id="navCixisBtn"><?= htmlspecialchars($t('ortaq.cixis')) ?></div>
    <?php endif; ?>
  </div>
</nav>

<?php if (!empty($girisEdilib)): ?>
<nav class="bottom-nav" id="bottomNav">
  <?php if (($rol ?? null) === 'musteri'): ?>
    <a class="bottom-nav-item<?= $hazirkiYol === '/panel' ? ' active' : '' ?>" href="/panel">
      <span class="bottom-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10"/></svg></span><span class="bottom-nav-label"><?= htmlspecialchars($t('ortaq.panel')) ?></span>
    </a>
  <?php elseif (in_array($rol ?? null, ['kurye', 'yukdasima'], true)): ?>
    <a class="bottom-nav-item<?= $hazirkiYol === '/lovhe' ? ' active' : '' ?>" href="/lovhe">
      <span class="bottom-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="M9 11h6M9 15h6M9 7h6"/></svg></span><span class="bottom-nav-label"><?= htmlspecialchars($t('kurye.lovhe')) ?></span>
    </a>
    <a class="bottom-nav-item<?= $hazirkiYol === '/sifarislerim' ? ' active' : '' ?>" href="/sifarislerim">
      <span class="bottom-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg></span><span class="bottom-nav-label"><?= htmlspecialchars($t('kurye.sifarislerim')) ?></span>
    </a>
    <a class="bottom-nav-item<?= $hazirkiYol === '/profil' ? ' active' : '' ?>" href="/profil">
      <span class="bottom-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg></span><span class="bottom-nav-label"><?= htmlspecialchars($t('profil.basliq')) ?></span>
    </a>
  <?php endif; ?>
</nav>
<?php endif; ?>

<div class="container">
