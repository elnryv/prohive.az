/* Birlikdə — paylaşılan client tərəfi köməkçiləri (framework yoxdur, Vanilla JS). */

window.Birlikde = (function () {
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

  /**
   * @param {string} method
   * @param {string} url
   * @param {Object|FormData|null} body
   * @returns {Promise<{ok: boolean, status: number, data: any}>}
   */
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

  function redirectIfUnauthorized(status, loginUrl) {
    if (status === 401) {
      window.location.href = loginUrl || '/giris';
      return true;
    }
    return false;
  }

  function switchLanguage(dil) {
    const url = new URL(window.location.href);
    url.searchParams.set('dil', dil);
    window.location.href = url.toString();
  }

  function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js').catch(function () {
        // Sessiz uğursuzluq — PWA dəstəklənməyən brauzerlərdə tətbiq normal işləməlidir.
      });
    }
  }

  // ---- Ana ekrana əlavə (bax bölmə 9.2.1-9.2.3) ----
  let deferredInstallPrompt = null;

  function isIos() {
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
  }

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  }

  function initInstallPrompt(overlayEl, addBtnEl, skipBtnEl, iosTextEl) {
    if (!overlayEl || isStandalone()) {
      return;
    }

    if (localStorage.getItem('birlikde_install_dismissed') === '1') {
      return;
    }

    if (isIos()) {
      if (iosTextEl) iosTextEl.style.display = 'block';
      if (addBtnEl) addBtnEl.style.display = 'none';
      overlayEl.classList.add('visible');
    } else {
      window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredInstallPrompt = event;
        overlayEl.classList.add('visible');
      });
    }

    if (addBtnEl) {
      addBtnEl.addEventListener('click', function () {
        if (deferredInstallPrompt) {
          deferredInstallPrompt.prompt();
          deferredInstallPrompt.userChoice.finally(function () {
            deferredInstallPrompt = null;
            overlayEl.classList.remove('visible');
          });
        }
      });
    }

    if (skipBtnEl) {
      skipBtnEl.addEventListener('click', function () {
        localStorage.setItem('birlikde_install_dismissed', '1');
        overlayEl.classList.remove('visible');
      });
    }
  }

  // ---- Web Push abunəliyi (bax bölmə 9.3) ----
  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; i++) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  async function subscribeToPush(vapidPublicKey) {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      return { supported: false };
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
      return { supported: true, granted: false };
    }

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    const json = subscription.toJSON();
    await api('POST', '/push/abune', {
      endpoint: json.endpoint,
      p256dh: json.keys.p256dh,
      auth: json.keys.auth,
    });

    return { supported: true, granted: true };
  }

  return {
    api: api,
    getCsrf: getCsrf,
    escapeHtml: escapeHtml,
    showError: showError,
    hideError: hideError,
    redirectIfUnauthorized: redirectIfUnauthorized,
    switchLanguage: switchLanguage,
    registerServiceWorker: registerServiceWorker,
    initInstallPrompt: initInstallPrompt,
    subscribeToPush: subscribeToPush,
    isIos: isIos,
    isStandalone: isStandalone,
  };
})();
