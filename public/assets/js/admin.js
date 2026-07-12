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

  // ---- Açılış (splash) animasiyası — admin giriş səhifəsi hər açılanda ----
  function playSplashOnce(splashEl) {
    if (!splashEl) {
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
  };
})();
