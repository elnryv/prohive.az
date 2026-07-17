<?php
$docRootLogo = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$splashLogoVer = @filemtime($docRootLogo . '/assets/icons/icon-512.png') ?: time();
?>
<div id="splash" class="splash" hidden>
  <div class="splash-inner">
    <img class="splash-logo" src="/assets/icons/icon-512.png?v=<?= $splashLogoVer ?>" alt="Birlikdə Yük" width="128" height="128">
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
