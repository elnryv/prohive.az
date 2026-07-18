<div id="splash" class="splash" hidden>
  <div class="splash-mark-wrapper">
    <div class="splash-glow"></div>
    <div class="splash-mark">
      <svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="blueGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#2196f3"/>
            <stop offset="100%" stop-color="#0d47a1"/>
          </linearGradient>
        </defs>
        <path class="b-letter" d="M70 40h100c44 0 74 28 74 68 0 26-12 46-34 58 28 10 46 34 46 66 0 44-34 72-80 72H70V40zm46 42v64h48c22 0 36-12 36-32s-14-32-36-32h-48zm0 102v74h54c24 0 40-14 40-37s-16-37-40-37h-54z" fill="url(#blueGradient)" />
        <line class="speed-line" x1="40" y1="190" x2="75" y2="190" stroke="#2196f3" stroke-width="4" stroke-linecap="round" />
        <line class="speed-line" x1="30" y1="205" x2="70" y2="205" stroke="#2196f3" stroke-width="4" stroke-linecap="round" />
        <line class="speed-line" x1="45" y1="220" x2="80" y2="220" stroke="#2196f3" stroke-width="4" stroke-linecap="round" />
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
