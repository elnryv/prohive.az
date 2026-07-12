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

  // ---- Sürüşən menyu (hamburger drawer) ----
  let closeDrawerFn = null;

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
    closeDrawerFn = closeDrawer;
  }

  // ---- Yüngül client-tərəfli marşrutlaşdırma (SPA-vari naviqasiya) ----
  // Məqsəd: eyni origin daxilində səhifədən-səhifəyə keçid (drawer linkləri,
  // profildəki "Sifarişlərim" keçidi, hüquqi sənəd siyahısı və s.) tam
  // səhifə yenilənməsi olmadan, dərhal baş versin. Login/qeydiyyat/çıxış və
  // dil dəyişimi ŞÜURLU ŞƏKİLDƏ bundan kənar saxlanılıb (window.location.href
  // ilə tam yenilənir) — çünki bunlar sessiya/rol vəziyyətini kökündən
  // dəyişir, drawer-in bütün məzmunu (rol-əsaslı menyu) yenidən server
  // tərəfdən render olunmalıdır.
  let pageLeaveCleanup = null;

  function onPageLeave(fn) {
    pageLeaveCleanup = fn;
  }

  function runContainerScripts(container) {
    var scripts = container.querySelectorAll('script');
    scripts.forEach(function (oldScript) {
      var newScript = document.createElement('script');
      for (var i = 0; i < oldScript.attributes.length; i++) {
        var attr = oldScript.attributes[i];
        newScript.setAttribute(attr.name, attr.value);
      }
      newScript.textContent = oldScript.textContent;
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
  }

  function updateDrawerActive(pathname) {
    document.querySelectorAll('.drawer-item[href]').forEach(function (link) {
      var href = link.getAttribute('href');
      if (!href || href.indexOf('/') !== 0) {
        return; // xarici keçid (məs. birlikde.biz) — toxunulmur
      }
      var aktiv = href === pathname || (href === '/huquqi' && pathname.indexOf('/huquqi/') === 0);
      link.classList.toggle('active', aktiv);
    });
  }

  async function navigate(url, pushHistory) {
    if (pushHistory === undefined) pushHistory = true;

    if (pageLeaveCleanup) {
      try {
        pageLeaveCleanup();
      } catch (e) {
        // sakitcə keç
      }
      pageLeaveCleanup = null;
    }

    var res;
    try {
      res = await fetch(url, { credentials: 'same-origin' });
    } catch (e) {
      window.location.href = url;
      return;
    }

    if (!res.ok) {
      window.location.href = url;
      return;
    }

    var html = await res.text();
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var newContainer = doc.querySelector('.container');
    var oldContainer = document.querySelector('.container');

    if (!newContainer || !oldContainer) {
      window.location.href = url;
      return;
    }

    if (closeDrawerFn) closeDrawerFn();

    // fetch() 3xx-i özü izləyir (məs. mövcud olmayan hüquqi sənəd slug-ı
    // /huquqi-yə server-tərəfdən yönləndirilir) — ünvan çubuğu SON gerçək
    // URL-i göstərməlidir, ilkin tıklanan linki yox.
    var finalUrl = res.url || url;

    document.title = doc.title || document.title;
    oldContainer.innerHTML = newContainer.innerHTML;
    runContainerScripts(oldContainer);

    var parsedUrl = new URL(finalUrl, window.location.href);
    updateDrawerActive(parsedUrl.pathname);

    if (pushHistory) {
      history.pushState({ birlikdeSpa: true }, '', parsedUrl.pathname + parsedUrl.search);
    }
    window.scrollTo(0, 0);
  }

  function initRouter() {
    document.body.addEventListener('click', function (event) {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }
      var link = event.target.closest('a');
      if (!link || !link.getAttribute('href')) {
        return;
      }
      if (link.target && link.target !== '_self') {
        return;
      }
      if (link.hasAttribute('download') || link.dataset.noSpa !== undefined) {
        return;
      }

      var url;
      try {
        url = new URL(link.href, window.location.href);
      } catch (e) {
        return;
      }
      if (url.origin !== window.location.origin) {
        return; // kənar keçid (birlikde.biz, wa.me və s.) — normal davranış
      }
      if (url.pathname === window.location.pathname && url.search === window.location.search) {
        return; // eyni səhifə (məs. #-keçidi) — normal davranış
      }

      event.preventDefault();
      navigate(url.pathname + url.search);
    });

    window.addEventListener('popstate', function () {
      navigate(window.location.pathname + window.location.search, false);
    });
  }

  // ---- Açılış (splash) animasiyası — tətbiqə TƏZƏ girəndə (yox, hər daxili
  // keçiddə) oynanılır; daxili keçid olub-olmadığı head.php-dəki sinxron
  // referrer-yoxlaması ilə müəyyənləşir (bax orada .hide əlavəsi) ----
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

  // Seçilmiş şəkli (HEIC daxil olmaqla — Safari <img>/canvas HEIC-i doğma
  // dəstəkləyir) kiçik, universal JPEG-ə çevirir. Serverin qəbul etmədiyi
  // formatlar (iPhone-un default HEIC-i kimi) ucbatından "yüklənmə göstərilmir"
  // problemini kökündən aradan qaldırır, həm də şəkli kiçildərək yükləməni
  // sürətləndirir.
  function imageToJpegBlob(file, maxDim, quality) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        URL.revokeObjectURL(url);
        var w = img.naturalWidth || img.width;
        var h = img.naturalHeight || img.height;
        var scale = Math.min(1, (maxDim || 640) / Math.max(w, h));
        var cw = Math.max(1, Math.round(w * scale));
        var ch = Math.max(1, Math.round(h * scale));
        var canvas = document.createElement('canvas');
        canvas.width = cw;
        canvas.height = ch;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, cw, ch);
        canvas.toBlob(function (blob) {
          if (blob) {
            resolve(blob);
          } else {
            reject(new Error('toBlob failed'));
          }
        }, 'image/jpeg', quality || 0.85);
      };
      img.onerror = function () {
        URL.revokeObjectURL(url);
        reject(new Error('Image load failed'));
      };
      img.src = url;
    });
  }

  return {
    api: api,
    getCsrf: getCsrf,
    escapeHtml: escapeHtml,
    showError: showError,
    hideError: hideError,
    redirectIfUnauthorized: redirectIfUnauthorized,
    switchLanguage: switchLanguage,
    initDrawer: initDrawer,
    playSplashOnce: playSplashOnce,
    registerServiceWorker: registerServiceWorker,
    initInstallPrompt: initInstallPrompt,
    subscribeToPush: subscribeToPush,
    isIos: isIos,
    isStandalone: isStandalone,
    imageToJpegBlob: imageToJpegBlob,
    initRouter: initRouter,
    navigate: navigate,
    onPageLeave: onPageLeave,
  };
})();
