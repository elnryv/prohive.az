<?php /** @var bool $forceShow Server "mütləq göstər" bayrağı — bax layouts/app.php. */ ?>
<div id="splash" class="splash" data-force="<?= !empty($forceShow) ? '1' : '0' ?>" hidden>
  <div class="splash-mark-wrapper">
    <div class="splash-glow"></div>
    <div class="splash-mark">
      <svg viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="blueGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#2196f3"/>
            <stop offset="100%" stop-color="#0d47a1"/>
          </linearGradient>
        </defs>
        <path d="M128,64a40,40,0,1,0,40,40A40,40,0,0,0,128,64Zm0,64a24,24,0,1,1,24-24A24,24,0,0,1,128,128Zm0-112a88.1,88.1,0,0,0-88,88c0,31.4,14.51,64.68,42,96.25a254.19,254.19,0,0,0,41.45,38.3,8,8,0,0,0,9.18,0A254.19,254.19,0,0,0,174,200.25c27.45-31.57,42-64.85,42-96.25A88.1,88.1,0,0,0,128,16Zm0,206c-16.53-13-72-60.75-72-118a72,72,0,0,1,144,0C200,161.23,144.53,209,128,222Z" fill="url(#blueGradient)" />
        <line class="speed-line" x1="30" y1="180" x2="65" y2="180" stroke="#2196f3" stroke-width="4" stroke-linecap="round" />
        <line class="speed-line" x1="20" y1="196" x2="60" y2="196" stroke="#2196f3" stroke-width="4" stroke-linecap="round" />
        <line class="speed-line" x1="34" y1="212" x2="68" y2="212" stroke="#2196f3" stroke-width="4" stroke-linecap="round" />
      </svg>
    </div>
  </div>

  <div class="splash-word">
    <h1 class="title">Birlikdə</h1>
    <h2 class="subtitle">Yük</h2>
    <p class="slogan"><?= e(t('home.splash_slogan')) ?></p>
  </div>

  <div class="splash-loadbar"><span></span></div>
</div>
<?php
// CSP (script-src 'self') xarici skriptə (GSAP CDN daxil) icazə vermir, buna görə
// xarici fayl kimi saxlanılır, lakin flaşsız işləmək üçün defer/async OLMADAN,
// elementdən dərhal sonra sinxron yüklənir.
$docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$splashJsVer = @filemtime($docRoot . '/assets/js/splash.js') ?: time();
?>
<script src="/assets/js/splash.js?v=<?= $splashJsVer ?>"></script>
