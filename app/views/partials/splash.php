<?php
$docRootLogo = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$splashLogoVer = @filemtime($docRootLogo . '/assets/icons/icon-512.png') ?: time();
?>
<div id="splash" class="splash" hidden>
  <div class="splash-particles" id="splash-particles"></div>

  <div class="splash-inner">
    <div class="splash-logo-wrapper">
      <span class="splash-streak splash-streak-1"></span>
      <span class="splash-streak splash-streak-2"></span>
      <span class="splash-streak splash-streak-3"></span>
      <span class="splash-glow"></span>
      <img class="splash-logo" src="/assets/icons/icon-512.png?v=<?= $splashLogoVer ?>" alt="Birlikdə Yük" width="128" height="128">
    </div>

    <div class="splash-text-wrapper">
      <h1 class="splash-title">Birlikdə</h1>
      <h2 class="splash-subtitle">Yük</h2>
      <p class="splash-slogan"><?= e(t('home.subtitle')) ?></p>
    </div>

    <div class="splash-loader-wrapper">
      <div class="splash-loader"><div class="splash-loader-bar" id="splash-loader-bar"></div></div>
      <span class="splash-loader-text" id="splash-loader-text">0%</span>
    </div>
  </div>
</div>
<?php
// CSP (script-src 'self') inline skriptə icazə vermir — buna görə xarici fayl, lakin
// splash effektinin flaşsız işləməsi üçün YENƏ DƏ defer/async OLMADAN, elementdən
// dərhal sonra sinxron yüklənir.
$docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$splashJsVer = @filemtime($docRoot . '/assets/js/splash.js') ?: time();
?>
<script src="/assets/js/splash.js?v=<?= $splashJsVer ?>"></script>
