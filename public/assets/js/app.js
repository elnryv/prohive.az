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

  // Telefon-əsaslı ağıllı giriş/qeydiyyat wizard-ı — bax auth/giris.php.
  // Kart-karuseli VƏ accordion konseptləri ləğv edilib (hər ikisi xüsusi
  // animasiya səbəbindən "donma" hissi yaradırdı). İndi 3 sadə addım
  // (.auth-step[data-step], `hidden` atributu ilə göstərilib/gizlədilir —
  // heç bir drag/grid/toggle-state riski yoxdur): telefon → POST
  // /telefon-yoxla → mövcuddursa "login" addımı, deyilsə "register" addımı.
  // `window.BirlikdeAuthWizard.telefon()` digər script-lərə (giris.php-də
  // yazılan giriş/qeydiyyat form handler-lərinə) tam telefonu (994...) verir.
  function isoToFlag(iso) {
    return iso.toUpperCase().replace(/./g, function (c) {
      return String.fromCodePoint(127397 + c.charCodeAt(0));
    });
  }

  function initAuthWizard(olkeKodlari) {
    var wizard = document.getElementById('authWizard');
    if (!wizard) return;

    var phoneForm = document.getElementById('phoneForm');
    var phoneDigits = document.getElementById('phoneDigits');
    var phoneErrBox = document.getElementById('phoneErrBox');
    var phoneHelper = document.getElementById('phoneHelper');
    var phoneInputGroup = document.getElementById('phoneInputGroup');
    var countrySelect = document.getElementById('countrySelect');
    var loginPhoneDisplay = document.getElementById('loginPhoneDisplay');
    var registerPhoneDisplay = document.getElementById('registerPhoneDisplay');
    var fullPhone = '';

    // olke-kodlari.js hər hansı səbəbdən (şəbəkə, keş, ad-blocker) yüklənə
    // bilməsə belə ölkə seçimi HEÇ VAXT boş qalmamalıdır — boş qalsa
    // countrySelect.value == '' olur və "+994" hissəsi tam yoxa çıxır
    // (bax PROGRESS.md — real bildirilmiş bug, kök səbəbi bu idi).
    var kodlar = (olkeKodlari && olkeKodlari.length) ? olkeKodlari : [['AZ', '994', 'Azərbaycan']];

    kodlar.forEach(function (olke) {
      var opt = document.createElement('option');
      opt.value = olke[1];
      opt.textContent = isoToFlag(olke[0]) + ' +' + olke[1];
      opt.title = olke[2];
      countrySelect.appendChild(opt);
    });

    function goToStep(step) {
      wizard.querySelectorAll('.auth-step').forEach(function (el) {
        el.hidden = el.dataset.step !== step;
      });
    }

    phoneForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      Birlikde.hideError(phoneErrBox);
      clearFieldError(phoneDigits, phoneHelper);
      phoneInputGroup.classList.remove('invalid');

      var digits = phoneDigits.value.replace(/\D/g, '');
      // Azərbaycan (994) seçilibsə operator kodu (10/50/51/55/70/77/99) + 7 rəqəm
      // formatı tələb olunur (bax AuthService::AZ_TELEFON_REGEX — server-tərəfdə
      // də eyni qayda). Digər ölkə kodları üçün yalnız minimal uzunluq yoxlanır.
      var validFormat = countrySelect.value === '994'
        ? /^(10|50|51|55|70|77|99)\d{7}$/.test(digits)
        : digits.length >= 7;
      if (!validFormat) {
        phoneInputGroup.classList.add('invalid');
        showFieldError(phoneDigits, phoneHelper, TELEFON_YANLIS_METNI);
        return;
      }
      fullPhone = countrySelect.value + digits;

      var submitBtn = document.getElementById('phoneContinueBtn');
      submitBtn.disabled = true;
      var res = await Birlikde.api('POST', '/telefon-yoxla', { telefon: fullPhone });
      submitBtn.disabled = false;

      if (!res.ok) {
        Birlikde.showError(phoneErrBox, (res.data && res.data.error) || XETA_METNI);
        return;
      }

      var displayPhone = '+' + countrySelect.value + ' ' + digits;
      if (res.data.movcuddur) {
        loginPhoneDisplay.textContent = displayPhone;
        goToStep('login');
        setTimeout(function () {
          var p = document.getElementById('parol');
          if (p) p.focus({ preventScroll: true });
        }, 350);
      } else {
        registerPhoneDisplay.textContent = displayPhone;
        goToStep('redirecting');
        setTimeout(function () {
          goToStep('register');
          setTimeout(function () {
            var a = document.getElementById('ad');
            if (a) a.focus({ preventScroll: true });
          }, 350);
        }, 1100);
      }
    });

    wizard.querySelectorAll('[data-back]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        goToStep('phone');
        setTimeout(function () { phoneDigits.focus({ preventScroll: true }); }, 350);
      });
    });

    window.BirlikdeAuthWizard = {
      telefon: function () { return fullPhone; }
    };
  }

  // Parol sahələrində göz-ikonu ilə göstər/gizlət (bax auth/giris.php,
  // `.password-field-wrap` + `.password-toggle[data-toggle-for]`).
  function initPasswordToggles() {
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.toggleFor);
        if (!input) return;
        var showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.textContent = showing ? '\u{1F441}' : '\u{1F648}';
      });
    });
  }

  // Sahə-səviyyəli inline validasiya əks-əlaqəsi (qırmızı sərhəd + konkret
  // köməkçi mətn) — bax app.css `.field-helper`/`.invalid`.
  function showFieldError(inputEl, helperEl, message) {
    inputEl.classList.add('invalid');
    if (helperEl) {
      helperEl.textContent = message;
      helperEl.classList.add('visible');
    }
  }

  function clearFieldError(inputEl, helperEl) {
    inputEl.classList.remove('invalid');
    if (helperEl) {
      helperEl.classList.remove('visible');
      helperEl.textContent = '';
    }
  }

  // Reklam banner karuseli (turbo.az tərzi — nə müştəriyə, nə kuryerə/
  // yükdaşımaya mane olmayan, ölçüsü sabit, avtomatik fırlanan zolaq).
  // Bax musteri/panel.php, kurye/lovhe.php. GET /bannerler rol-a görə
  // (session-dan) uyğun bannerləri qaytarır — bax BannerController.
  async function initBannerCarousel(containerId, dotsId) {
    var container = document.getElementById(containerId);
    var dotsEl = document.getElementById(dotsId);
    if (!container || !dotsEl) return;

    var res = await api('GET', '/bannerler');
    if (!res.ok) return;
    var banners = (res.data && res.data.data) || [];
    if (banners.length === 0) return;

    banners.forEach(function (b, i) {
      var slide = document.createElement('div');
      slide.className = 'banner-slide' + (i === 0 ? ' active' : '');

      var inner = document.createElement(b.link ? 'a' : 'div');
      if (b.link) {
        inner.href = b.link;
        inner.target = '_blank';
        inner.rel = 'noopener';
      }
      var img = document.createElement('img');
      img.src = b.sekil_url;
      img.alt = b.baslik || '';
      img.loading = 'lazy';
      inner.appendChild(img);
      slide.appendChild(inner);
      container.insertBefore(slide, dotsEl);

      if (banners.length > 1) {
        var dot = document.createElement('span');
        dot.className = 'banner-dot' + (i === 0 ? ' active' : '');
        dotsEl.appendChild(dot);
      }
    });

    container.hidden = false;

    if (banners.length > 1) {
      var current = 0;
      setInterval(function () {
        var slides = container.querySelectorAll('.banner-slide');
        var dots = dotsEl.querySelectorAll('.banner-dot');
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
      }, 4000);
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
    document.querySelectorAll('.drawer-item[href], .bottom-nav-item[href]').forEach(function (link) {
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
  // referrer-yoxlaması ilə müəyyənləşir (bax orada .hide əlavəsi). İstifadəçi
  // istinad video-sundakı radar-puls ritminə uyğun — tam CSS (bax app.css
  // `.splash-word .brand-mark::before/::after`), Canvas/JS lazım deyil.
  function playSplashOnce(splashEl) {
    if (!splashEl || splashEl.classList.contains('hide')) {
      return;
    }

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    setTimeout(function () {
      splashEl.classList.add('hide');
    }, reduced ? 0 : 1900);
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
    initAuthWizard: initAuthWizard,
    initBannerCarousel: initBannerCarousel,
    initPasswordToggles: initPasswordToggles,
    showFieldError: showFieldError,
    clearFieldError: clearFieldError,
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
