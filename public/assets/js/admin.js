/* Birlikdə Admin — client tərəfi köməkçiləri (framework yoxdur, Vanilla JS). */

window.BirlikdeAdmin = (function () {
  'use strict';

  let csrfToken = null;

  async function getCsrf() {
    if (csrfToken) {
      return csrfToken;
    }
    const res = await fetch('/csrf-token', { credentials: 'same-origin' });
    const data = await res.json();
    csrfToken = data.csrf_token;
    return csrfToken;
  }

  async function api(method, url, body) {
    const opts = { method, credentials: 'same-origin', headers: {} };

    if (method !== 'GET') {
      opts.headers['X-CSRF-Token'] = await getCsrf();
    }

    if (body instanceof FormData) {
      opts.body = body;
    } else if (body) {
      opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      opts.body = new URLSearchParams(body).toString();
    }

    const res = await fetch(url, opts);
    let data = null;
    try {
      data = await res.json();
    } catch (e) {
      data = null;
    }

    return { ok: res.ok, status: res.status, data: data };
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value === null || value === undefined ? '' : String(value);
    return div.innerHTML;
  }

  function showError(el, message) {
    if (!el) return;
    el.textContent = message;
    el.classList.add('visible');
  }

  function hideError(el) {
    if (!el) return;
    el.classList.remove('visible');
    el.textContent = '';
  }

  function redirectIfUnauthorized(status) {
    if (status === 401) {
      window.location.href = '/giris';
      return true;
    }
    return false;
  }

  // ---- Mobil üçün sürüşən (off-canvas) yan-menyu — bax shell_head.php.
  // Masaüstündə .admin-nav sabit sidebar olaraq qalır (CSS media query xaricində
  // heç bir təsiri yoxdur), yalnız 860px-dən dar ekranlarda işə düşür ----
  function initDrawer(burgerEl, drawerEl, scrimEl) {
    if (!burgerEl || !drawerEl || !scrimEl) return;

    function openDrawer() {
      drawerEl.classList.add('open');
      scrimEl.classList.add('visible');
      burgerEl.classList.add('open');
    }
    function closeDrawer() {
      drawerEl.classList.remove('open');
      scrimEl.classList.remove('visible');
      burgerEl.classList.remove('open');
    }

    burgerEl.addEventListener('click', function () {
      drawerEl.classList.contains('open') ? closeDrawer() : openDrawer();
    });
    scrimEl.addEventListener('click', closeDrawer);
  }

  // ---- Cədvəllər üfüqi sürüşəndə sağ kənarda "daha çox var" ipucu ----
  // Cədvəllər (musteriler.php, kuryerler.php və s.) JS ilə dinamik doldurulur,
  // ona görə hər fayla toxunmadan bir dəfə bütün .table-wrap-admin
  // elementlərini MutationObserver ilə izləyirik.
  function initTableScrollHints() {
    var wraps = document.querySelectorAll('.table-wrap-admin');
    wraps.forEach(function (wrap) {
      function update() {
        wrap.classList.toggle('has-overflow', wrap.scrollWidth - wrap.scrollLeft > wrap.clientWidth + 2);
      }
      update();
      new MutationObserver(update).observe(wrap, { childList: true, subtree: true });
      wrap.addEventListener('scroll', update);
      window.addEventListener('resize', update);
    });
  }

  // ---- Açılış (splash) animasiyası — admin girişinə TƏZƏ girəndə (daxili
  // yönləndirmədə yox) oynanılır; bax login_head.php-dəki referrer yoxlaması ----
  function playSplashOnce(splashEl) {
    if (!splashEl || splashEl.classList.contains('hide')) {
      return;
    }

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!reduced) {
      var targets = [
        { dx: -0.5, dy: -0.6 },
        { dx: 0.55, dy: -0.45 },
        { dx: -0.58, dy: 0.5 },
        { dx: 0.62, dy: 0.5 },
        { dx: 0.02, dy: -0.68 },
      ];
      var blobs = splashEl.querySelectorAll('.splash-blob');
      blobs.forEach(function (el, i) {
        var t = targets[i] || targets[0];
        var endX = t.dx * window.innerWidth;
        var endY = t.dy * window.innerHeight;
        el.style.left = '50%';
        el.style.top = '50%';
        el.style.marginLeft = (-el.offsetWidth / 2) + 'px';
        el.style.marginTop = (-el.offsetHeight / 2) + 'px';
        el.style.setProperty('--end', 'translate(' + endX + 'px,' + endY + 'px)');
        el.style.animationDelay = (i * 0.05) + 's';
      });
    }

    setTimeout(function () {
      splashEl.classList.add('hide');
    }, reduced ? 0 : 2000);
  }

  return {
    api: api,
    getCsrf: getCsrf,
    escapeHtml: escapeHtml,
    showError: showError,
    hideError: hideError,
    redirectIfUnauthorized: redirectIfUnauthorized,
    playSplashOnce: playSplashOnce,
    initDrawer: initDrawer,
    initTableScrollHints: initTableScrollHints,
  };
})();
