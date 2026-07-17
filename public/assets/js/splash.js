// Sessiyada 1 dəfə (bölmə 10.2, FAZA 15) — flaş effektindən qaçmaq üçün dərhal
// (defer olmadan) icra olunur. Bütün açılış "teatr"ı CSS keyframe-lərlə (bax
// app.css .splash-*) idarə olunur, bu skript yalnız: (1) hissəcikləri generasiya edir,
// (2) loading bar/faiz sayğacını sürükləyir, (3) sonunda splash-ı sildirir.
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
    var container = document.getElementById('splash-particles');
    if (!container || reduceMotion) return;
    var count = 26;
    for (var i = 0; i < count; i++) {
      var p = document.createElement('span');
      p.className = 'splash-particle';
      p.style.left = Math.round(Math.random() * 100) + '%';
      p.style.top = Math.round(Math.random() * 100) + '%';
      p.style.setProperty('--dur', (2 + Math.random() * 2.5).toFixed(2) + 's');
      p.style.setProperty('--delay', (Math.random() * 2).toFixed(2) + 's');
      p.style.setProperty('--dy', '-' + Math.round(20 + Math.random() * 40) + 'px');
      p.style.setProperty('--peak', (0.35 + Math.random() * 0.35).toFixed(2));
      container.appendChild(p);
    }
  }

  function runLoader(delayMs, durationMs, onDone) {
    var bar = document.getElementById('splash-loader-bar');
    var text = document.getElementById('splash-loader-text');
    if (!bar || !text) { setTimeout(onDone, delayMs + durationMs); return; }
    setTimeout(function () {
      var start = null;
      function tick(ts) {
        if (start === null) start = ts;
        var elapsed = ts - start;
        var t = Math.min(1, elapsed / durationMs);
        var eased = 1 - Math.pow(1 - t, 4); // power4-out bənzəri
        var pct = Math.round(eased * 100);
        bar.style.width = pct + '%';
        text.textContent = pct + '%';
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
    setTimeout(function () { splash.remove(); }, 550);
  }

  createParticles();

  if (reduceMotion) {
    // Animasiyasız halda dərhal sil — istifadəçini uzun boş ekranda gözlətmə.
    hideSplash();
    return;
  }

  runLoader(2050, 1400, function () {
    setTimeout(hideSplash, 200);
  });
})();
