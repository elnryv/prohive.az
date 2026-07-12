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

  // Ünvan marşrutunu (götürülmə → çatdırılma) vertikal stepper kimi göstərən
  // paylaşılan HTML parçası — bax public/assets/css/app.css .route-stepper.
  function routeStepperHtml(fromAddr, toAddr, fromLabel, toLabel) {
    return (
      '<div class="route-stepper">' +
        '<div class="route-point">' +
          '<span class="route-dot route-dot-start"></span>' +
          '<div><span class="route-label">' + escapeHtml(fromLabel) + '</span>' +
          '<span class="route-addr">' + escapeHtml(fromAddr) + '</span></div>' +
        '</div>' +
        '<div class="route-point">' +
          '<span class="route-dot route-dot-end"></span>' +
          '<div><span class="route-label">' + escapeHtml(toLabel) + '</span>' +
          '<span class="route-addr">' + escapeHtml(toAddr) + '</span></div>' +
        '</div>' +
      '</div>'
    );
  }

  // Giriş/Qeydiyyat kart karuseli — bax auth/giris.php, auth/qeydiyyat.php
  // (#authDeck data-my-rol="giris|qeydiyyat"). Ön kartı barmaqla/mouse ilə
  // real-vaxtda sürüşdürmək olar (Pointer Events, --dragX/--dragRot CSS
  // dəyişənləri ilə, bax app.css `.deck-card-front`); yetərincə sürüşdürülsə
  // digər karta keçir, azca hərəkət olarsa toxunma (tap) kimi qəbul edilib
  // forma açılır. Arxa kartın görünən kənarına toxunmaq da (drag olmadan)
  // ona keçid edir.
  function initAuthDeck() {
    var deck = document.getElementById('authDeck');
    if (!deck) return;
    var formWrap = document.getElementById('authFormWrap');
    var myRol = deck.dataset.myRol;
    var cards = deck.querySelectorAll('.deck-card');
    var SWIPE_THRESHOLD = 70;

    function otherCard(card) {
      for (var i = 0; i < cards.length; i++) {
        if (cards[i] !== card) return cards[i];
      }
      return null;
    }

    function setFront(rol) {
      cards.forEach(function (c) {
        var isFront = c.dataset.target === rol;
        c.classList.toggle('deck-card-front', isFront);
        c.classList.toggle('deck-card-back', !isFront);
      });
    }

    setFront(myRol);

    function openForm() {
      deck.classList.add('selecting');
      setTimeout(function () {
        deck.classList.add('leaving');
        setTimeout(function () {
          deck.setAttribute('hidden', '');
          formWrap.removeAttribute('hidden');
          formWrap.classList.add('reveal');
          var firstInput = formWrap.querySelector('input, select, textarea');
          if (firstInput) firstInput.focus({ preventScroll: true });
        }, 400);
      }, 260);
    }

    function switchTo(target) {
      setFront(target);
      setTimeout(function () {
        window.location.href = '/' + target + '?open=1';
      }, 340);
    }

    cards.forEach(function (card) {
      var dragging = false;
      var moved = false;
      var startX = 0;
      var activePointerId = null;

      card.addEventListener('pointerdown', function (e) {
        if (card.dataset.target !== myRol) return;
        dragging = true;
        moved = false;
        startX = e.clientX;
        activePointerId = e.pointerId;
        card.classList.add('dragging');
        try { card.setPointerCapture(activePointerId); } catch (err) { /* noop */ }
      });

      card.addEventListener('pointermove', function (e) {
        if (!dragging || e.pointerId !== activePointerId) return;
        var dx = e.clientX - startX;
        if (Math.abs(dx) > 6) moved = true;
        card.style.setProperty('--dragX', dx + 'px');
        card.style.setProperty('--dragRot', (dx / 14) + 'deg');
      });

      function endDrag(e) {
        if (!dragging) return;
        dragging = false;
        card.classList.remove('dragging');
        var dx = e.clientX - startX;

        if (Math.abs(dx) > SWIPE_THRESHOLD) {
          var flyTo = dx > 0 ? 620 : -620;
          card.style.setProperty('--dragX', flyTo + 'px');
          card.style.setProperty('--dragRot', (dx > 0 ? 34 : -34) + 'deg');
          var target = otherCard(card);
          setTimeout(function () {
            card.style.removeProperty('--dragX');
            card.style.removeProperty('--dragRot');
            if (target) switchTo(target.dataset.target);
          }, 260);
        } else {
          card.style.removeProperty('--dragX');
          card.style.removeProperty('--dragRot');
          if (!moved) openForm();
        }
      }

      card.addEventListener('pointerup', endDrag);
      card.addEventListener('pointercancel', function () {
        dragging = false;
        card.classList.remove('dragging');
        card.style.removeProperty('--dragX');
        card.style.removeProperty('--dragRot');
      });

      card.addEventListener('click', function () {
        if (card.dataset.target !== myRol) {
          switchTo(card.dataset.target);
        }
      });
    });

    if (/[?&]open=1\b/.test(window.location.search)) {
      setTimeout(openForm, 200);
    }
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
  // Kanvas hissəcik "enerji partlayışı" — WebGL/Three.js əvəzinə yüngül Canvas 2D
  // (aşağı-səviyyəli Android telefonlarda da rahat işləməsi üçün). Mərkəzdən
  // spiral şəklində genişlənən, marka rənglərində (mavi/narıncı/çəhrayı/yaşıl)
  // parlaq hissəciklər, sonda halqa şəklində sabitləşir və sözlə birgə sönür.
  function runSplashParticles(canvas) {
    var ctx = canvas.getContext('2d');
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var w = window.innerWidth;
    var h = window.innerHeight;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    ctx.scale(dpr, dpr);

    var cx = w / 2;
    var cy = h / 2;
    var colors = ['#3d5afe', '#ff7a3d', '#ff4d8f', '#22c55e', '#ffb020'];
    var COUNT = 70;
    var particles = [];
    for (var i = 0; i < COUNT; i++) {
      var angle = (Math.PI * 2 * i) / COUNT + Math.random() * 0.4;
      particles.push({
        angle: angle,
        spin: (Math.random() - 0.5) * 0.03,
        radius: 0,
        maxRadius: 70 + Math.random() * (Math.min(w, h) * 0.32),
        speed: 2.2 + Math.random() * 2.4,
        size: 2 + Math.random() * 3,
        color: colors[i % colors.length],
      });
    }

    var start = null;
    var DURATION = 1500;
    var rafId = null;

    function frame(ts) {
      if (!start) start = ts;
      var elapsed = ts - start;
      var t = Math.min(elapsed / DURATION, 1);
      var ease = 1 - Math.pow(1 - t, 3);

      ctx.clearRect(0, 0, w, h);

      particles.forEach(function (p) {
        p.angle += p.spin;
        var r = p.maxRadius * ease;
        var x = cx + Math.cos(p.angle) * r;
        var y = cy + Math.sin(p.angle) * r;
        var fade = t < 0.75 ? 1 : Math.max(0, 1 - (t - 0.75) / 0.25);

        var grad = ctx.createRadialGradient(x, y, 0, x, y, p.size * 3);
        grad.addColorStop(0, p.color);
        grad.addColorStop(1, 'rgba(255,255,255,0)');
        ctx.globalAlpha = fade;
        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.arc(x, y, p.size * 3, 0, Math.PI * 2);
        ctx.fill();

        ctx.globalAlpha = fade;
        ctx.fillStyle = p.color;
        ctx.beginPath();
        ctx.arc(x, y, p.size, 0, Math.PI * 2);
        ctx.fill();
      });
      ctx.globalAlpha = 1;

      if (t < 1) {
        rafId = requestAnimationFrame(frame);
      }
    }

    rafId = requestAnimationFrame(frame);

    return function stop() {
      if (rafId) cancelAnimationFrame(rafId);
      ctx.clearRect(0, 0, w, h);
    };
  }

  function playSplashOnce(splashEl) {
    if (!splashEl || splashEl.classList.contains('hide')) {
      return;
    }

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var stopParticles = null;

    if (!reduced) {
      var canvas = splashEl.querySelector('#splashCanvas');
      if (canvas && canvas.getContext) {
        stopParticles = runSplashParticles(canvas);
      }
    }

    setTimeout(function () {
      splashEl.classList.add('hide');
      if (stopParticles) stopParticles();
    }, reduced ? 0 : 1650);
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

  function isIosStandalone() {
    return window.navigator.standalone === true
      || window.matchMedia('(display-mode: standalone)').matches;
  }

  function isIosSafari() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
  }

  async function subscribeToPush(vapidPublicKey) {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      // iOS Safari-də Push API YALNIZ "Ana ekrana əlavə et" ilə PWA kimi
      // quraşdırılandan sonra mövcuddur (Apple-ın öz məhdudiyyəti) — adi
      // Safari-də bu tamamilə gözlənilən haldır, real bug deyil.
      if (isIosSafari() && !isIosStandalone()) {
        return { supported: false, iosNotInstalled: true };
      }
      return { supported: false };
    }
    if (!vapidPublicKey) {
      return { supported: true, granted: false, error: 'vapid_missing' };
    }

    try {
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        return { supported: true, granted: false, denied: true };
      }

      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
      });

      const json = subscription.toJSON();
      const res = await api('POST', '/push/abune', {
        endpoint: json.endpoint,
        p256dh: json.keys.p256dh,
        auth: json.keys.auth,
      });

      if (!res.ok) {
        return { supported: true, granted: true, error: 'server' };
      }

      return { supported: true, granted: true };
    } catch (e) {
      return { supported: true, granted: false, error: (e && e.message) || 'unknown' };
    }
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
    initAuthDeck: initAuthDeck,
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
    routeStepperHtml: routeStepperHtml,
  };
})();
