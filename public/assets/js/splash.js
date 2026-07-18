// Flaş effektindən qaçmaq üçün dərhal (defer olmadan) icra olunur. Bütün açılış
// ardıcıllığı (loqo, mətn, loader) CSS keyframe `animation-delay`-lə idarə olunur
// (bax app.css .splash .* qaydaları). Bu skript yalnız: (1) hissəcikləri generasiya
// edir, (2) loading bar/faiz sayğacını sürükləyir, (3) sonda splash-ı sildirir.
//
// FAZA 16 fix: əvvəllər `sessionStorage` istifadə olunurdu ("sessiyada 1 dəfə"), amma
// PWA-nı Ana ekrandan açanlarda (standalone rejim) mobil brauzerlər (xüsusən iOS)
// arxa plana atılan səhifənin WebView prosesini yaddaş üçün öldürüb sonra "təzə"
// yükləyə bilir — bu zaman sessionStorage sıfırlanır və splash HƏR dəfə arxa plandan
// qayıdanda təkrar oynanılır (şikayət budur). `localStorage` isə disk-əsaslıdır,
// WebView prosesi öldürülsə belə davam edir — buna görə saxlanma yeri dəyişdirilib,
// üstəlik 12 saatdan köhnə olarsa yenidən göstərilir (yeni günün ilk açılışı kimi).
(function () {
  var splash = document.getElementById('splash');
  if (!splash) return;

  var STORAGE_KEY = 'yuk_splash_last_shown';
  var MIN_GAP_MS = 12 * 60 * 60 * 1000; // 12 saat
  var lastShown = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
  if (lastShown && (Date.now() - lastShown) < MIN_GAP_MS) {
    splash.remove();
    return;
  }
  localStorage.setItem(STORAGE_KEY, String(Date.now()));
  splash.hidden = false;

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function createParticles() {
    var container = document.getElementById('particles');
    if (!container) return;
    var count = 50;
    for (var i = 0; i < count; i++) {
      var p = document.createElement('div');
      p.className = 'particle';
      p.style.left = Math.round(Math.random() * 100) + 'vw';
      p.style.top = Math.round(Math.random() * 100) + 'vh';
      p.style.setProperty('--dur', (Math.random() * 2 + 1).toFixed(2) + 's');
      p.style.setProperty('--delay', (Math.random() * 2).toFixed(2) + 's');
      p.style.setProperty('--dy', (Math.round(Math.random() * 100 - 50)) + 'px');
      p.style.setProperty('--peak', (Math.random() * 0.6 + 0.2).toFixed(2));
      container.appendChild(p);
    }
  }

  function updateLoader(pct) {
    var bar = document.getElementById('loaderBar');
    var text = document.getElementById('loaderText');
    if (bar) bar.style.width = pct + '%';
    if (text) text.textContent = pct + '%';
  }

  function runLoader(delayMs, durationMs, onDone) {
    setTimeout(function () {
      var start = null;
      function tick(ts) {
        if (start === null) start = ts;
        var elapsed = ts - start;
        var t = Math.min(1, elapsed / durationMs);
        var eased = 1 - Math.pow(1 - t, 3); // power3-out bənzəri (GSAP defaults.ease)
        updateLoader(Math.round(eased * 100));
        if (t < 1) {
          requestAnimationFrame(tick);
        } else {
          onDone();
        }
      }
      requestAnimationFrame(tick);
    }, delayMs);
  }

  function hideSplash() {
    splash.classList.add('splash-hide');
    setTimeout(function () { splash.remove(); }, 650);
  }

  if (reduceMotion) {
    // Animasiyasız halda istifadəçini uzun teatr boyu gözlətmə — dərhal tam vəziyyətə
    // keç və qısa müddətdən sonra sil.
    updateLoader(100);
    setTimeout(hideSplash, 300);
    return;
  }

  createParticles();

  // Loader-bar 2.7s (loader-wrapper) qalxdıqdan qısa müddət sonra, 3.2s-də başlayır, 2s çəkir.
  runLoader(3200, 2000, function () {
    setTimeout(hideSplash, 200);
  });
})();
