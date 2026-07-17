// Sessiyada 1 dəfə (bölmə 10.2, FAZA 15) — flaş effektindən qaçmaq üçün dərhal
// (defer olmadan) icra olunur. Bütün açılış ardıcıllığı (loqo hissələrinin sıra ilə
// yığılması, mətn, loader) CSS keyframe `animation-delay`-lə idarə olunur (bax app.css
// .splash .* qaydaları — sahibkarın verdiyi GSAP timeline-ın eyni vaxt cədvəli ilə).
// Bu skript yalnız: (1) hissəcikləri generasiya edir, (2) loading bar/faiz sayğacını
// GSAP-ın loader-bar tween-inin başladığı andan (3.8s) etibarən sürükləyir, (3) sonda
// splash-ı sildirir.
(function () {
  var splash = document.getElementById('splash');
  if (!splash) return;

  if (sessionStorage.getItem('yuk_splash_shown')) {
    splash.remove();
    return;
  }
  sessionStorage.setItem('yuk_splash_shown', '1');
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

  // GSAP ssenarisindəki loader-bar tween-i 3.8s-də başlayır, 2s çəkir.
  runLoader(3800, 2000, function () {
    setTimeout(hideSplash, 200);
  });
})();
