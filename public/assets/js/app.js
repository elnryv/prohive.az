// Birlikdə Yük — client tərəfi: Service Worker, Web Push abunəliyi, SSE canlı yeniləmə.
(function () {
  'use strict';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const body = document.body;

  // ---------------------------------------------------------------
  // Service Worker
  // ---------------------------------------------------------------
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
  }

  // ---------------------------------------------------------------
  // PWA quraşdırma sheet-i (Q-Y13, bölmə 11.3)
  // ---------------------------------------------------------------
  const DISMISS_KEY = 'yuk_install_dismissed_at';
  const INSTALLED_KEY = 'yuk_installed';
  const DISMISS_DAYS = 3;

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  }
  function isIos() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
  }
  function dismissedRecently() {
    const at = localStorage.getItem(DISMISS_KEY);
    if (!at) return false;
    return (Date.now() - parseInt(at, 10)) < DISMISS_DAYS * 86400000;
  }

  let deferredInstallPrompt = null;
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredInstallPrompt = e;
  });
  window.addEventListener('appinstalled', () => {
    localStorage.setItem(INSTALLED_KEY, '1');
    hideInstallSheet();
    hideInstallReminder();
  });

  const sheet = document.getElementById('install-sheet');
  const reminder = document.getElementById('install-reminder');

  function showInstallSheet() {
    if (!sheet || isStandalone() || localStorage.getItem(INSTALLED_KEY) === '1') {
      return;
    }
    const androidBox = document.getElementById('install-android');
    const iosBox = document.getElementById('install-ios');
    if (isIos()) {
      iosBox.hidden = false;
      androidBox.hidden = true;
    } else {
      androidBox.hidden = false;
      iosBox.hidden = true;
    }
    sheet.hidden = false;
  }
  function hideInstallSheet() {
    if (sheet) sheet.hidden = true;
  }
  function showInstallReminder() {
    if (!reminder || isStandalone() || localStorage.getItem(INSTALLED_KEY) === '1') {
      return;
    }
    reminder.hidden = false;
  }
  function hideInstallReminder() {
    if (reminder) reminder.hidden = true;
  }

  document.getElementById('install-close')?.addEventListener('click', () => {
    localStorage.setItem(DISMISS_KEY, String(Date.now()));
    hideInstallSheet();
  });
  document.getElementById('install-reminder-close')?.addEventListener('click', hideInstallReminder);
  document.getElementById('install-reminder-btn')?.addEventListener('click', showInstallSheet);
  document.getElementById('install-android-btn')?.addEventListener('click', async () => {
    if (!deferredInstallPrompt) {
      return;
    }
    deferredInstallPrompt.prompt();
    const choice = await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    if (choice.outcome === 'accepted' && 'Notification' in window && Notification.permission === 'default') {
      const perm = await Notification.requestPermission();
      if (perm === 'granted' && window.YukPush) {
        window.YukPush.subscribe();
      }
    }
  });

  const pushAlreadyGranted = typeof Notification !== 'undefined' && Notification.permission === 'granted';

  if (!isStandalone() && localStorage.getItem(INSTALLED_KEY) !== '1') {
    // Qeydiyyat/giriş bitən kimi (bölmə 6.1): tam ekran sheet, 3 gün cooldown ilə.
    const params = new URLSearchParams(window.location.search);
    if (params.get('xosgeldin') === '1' && !dismissedRecently()) {
      showInstallSheet();
    }
    // Hər iki roldan (sürücü lenti / müştəri elanlarım) əsas ekranı açanda nazik
    // xatırlatma zolağı — push bildirişi kritik olduğu üçün cooldown-a tabe deyil,
    // artıq icazə verilibsə (permission=granted) bezdirməmək üçün göstərilmir.
    const onDriverFeed = body.dataset.role === 'driver' && document.getElementById('feed-list');
    const onCustomerDashboard = body.dataset.role === 'customer' && document.querySelector('.dash-cta');
    if ((onDriverFeed || onCustomerDashboard) && !pushAlreadyGranted) {
      showInstallReminder();
    }
  }

  // ---------------------------------------------------------------
  // Web Push abunəliyi (bölmə 11.4) — icazə artıq verilibsə səssiz abunə olunur.
  // Aktiv tələb (permission prompt) FAZA 7-dəki quraşdırma axınında idarə olunur.
  // ---------------------------------------------------------------
  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
  }

  async function subscribeToPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      return;
    }
    if (body.dataset.auth !== '1') {
      return;
    }
    try {
      const reg = await navigator.serviceWorker.ready;
      let sub = await reg.pushManager.getSubscription();
      if (!sub) {
        const res = await fetch('/push/vapid-acar');
        const { key } = await res.json();
        if (!key) {
          return;
        }
        sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(key),
        });
      }
      await fetch('/push/abune', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify(sub.toJSON()),
      });
    } catch (err) {
      // Sakit uğursuzluq — push kritik funksiya deyil, sayt onsuz da işləyir.
    }
  }

  window.YukPush = { subscribe: subscribeToPush };

  if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
    subscribeToPush();
  }

  // Profil səhifəsindəki "Bildirişləri aç" düyməsi — iOS toxunma-tələbini
  // ödəmək üçün ancaq real klik daxilində Notification.requestPermission()
  // çağırılır (avtomatik banner/aşkarlama etibarsız çıxdı, bax PROGRESS.md).
  const pushBtn = document.getElementById('profile-push-btn');
  const pushSuccessEl = document.getElementById('profile-push-success');
  const pushErrorEl = document.getElementById('profile-push-error');
  pushBtn?.addEventListener('click', async () => {
    if (pushSuccessEl) pushSuccessEl.hidden = true;
    if (pushErrorEl) pushErrorEl.hidden = true;
    pushBtn.disabled = true;
    try {
      if (typeof Notification === 'undefined' || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        if (pushErrorEl) { pushErrorEl.hidden = false; pushErrorEl.textContent = pushBtn.dataset.msgUnsupported; }
        return;
      }
      const perm = await Notification.requestPermission();
      if (perm !== 'granted') {
        if (pushErrorEl) { pushErrorEl.hidden = false; pushErrorEl.textContent = pushBtn.dataset.msgDenied; }
        return;
      }
      await subscribeToPush();
      if (pushSuccessEl) pushSuccessEl.hidden = false;
    } finally {
      pushBtn.disabled = false;
    }
  });

  // ---------------------------------------------------------------
  // "Bağlantı yoxdur" zolağı (FAZA 14/16): YALNIZ brauzerin öz native
  // online/offline siqnalına əsaslanır — SSE-nin "error" hadisəsinə əsla bağlı
  // deyil. Səbəb: server (Sse::stream) hər ~55 saniyədə bağlantını QƏSDƏN bağlayır
  // (uzun-polling dövrü) və bu, tam sağlam bağlantıda da normal/gözlənilən "error"
  // yaradır — SSE-ni siqnal kimi istifadə etmək yalan-müsbətlərə səbəb olurdu
  // (canlı testdə görüldü). `navigator.onLine`/`online`/`offline` isə əməliyyat
  // sisteminin özünün bildirdiyi həqiqi bağlantı vəziyyətidir.
  const connBanner = document.getElementById('conn-status-banner');
  if (connBanner) {
    connBanner.hidden = navigator.onLine;
    window.addEventListener('offline', () => { connBanner.hidden = false; });
    window.addEventListener('online', () => { connBanner.hidden = true; });
  }

  // ---------------------------------------------------------------
  // SSE (bölmə 11.5, FAZA 34): FAZA 31-də app.birlikde.biz-in minimal
  // `connectSse()`-inə uyğunlaşdırılmışdı (yalnız EventSource, `onerror` boş) —
  // CANLI olaraq sahibkar bildirdi ki, bu kifayət etmir: bildiriş gəlir, amma
  // lent yenilənmir, YALNIZ başqa səhifəyə keçib qayıdanda (yəni səhifə TAM
  // yenidən yüklənəndə) görünür. Bu, məhz FAZA 27-də tapılmış (və FAZA 31-də
  // sahibkarın öz açıq göstərişi ilə silinmiş) Safari/WebKit bugının simptomudur:
  // arxa fonda olan/ekranı kilidlənmiş tab-da EventSource-un daxili bağlantısı
  // sükutla ölür (`onerror` işə düşmədən CONNECTING-də donur, ya da CLOSED-a
  // keçir amma brauzerin öz avtomatik-reconnect-i heç vaxt tətikeşmir) — tab
  // yenidən görünən olanda BUNU YOXLAYIB özümüz bərpa etməliyik, çünki brauzerə
  // etibar bu ssenaridə iflasa uğrayır. Bu dəfə YALNIZ bu bir konkret defekt üçün,
  // minimal şəkildə (əvvəlki 90s watchdog/polling-fallback kimi əlavə qatlar
  // YOXDUR) — `visibilitychange`-də vəziyyət YENİDƏN yoxlanılır, "OPEN" deyilsə
  // məcburi bağlanıb təzədən açılır.
  // ---------------------------------------------------------------
  function connectResilientSSE(url, initialLastId, handlers) {
    let es = null;
    let lastId = initialLastId;
    let reconnectTimer = null;

    function scheduleReconnect(delayMs) {
      if (reconnectTimer) return;
      reconnectTimer = setTimeout(() => {
        reconnectTimer = null;
        open();
      }, delayMs);
    }

    function open() {
      const sep = url.includes('?') ? '&' : '?';
      const fullUrl = url + sep + 'lastId=' + encodeURIComponent(lastId);
      es = new EventSource(fullUrl);
      es.onerror = () => {
        // Brauzer adətən özü yenidən qoşulmağa cəhd edir, AMMA əgər bağlantı
        // artıq HƏQİQƏTƏN bağlıdırsa (CLOSED) və brauzer öz reconnect-ini
        // başlatmayıbsa, bir saniyə sonra özümüz təzələyirik.
        if (es && es.readyState === EventSource.CLOSED) {
          scheduleReconnect(1000);
        }
      };
      Object.keys(handlers).forEach((name) => {
        es.addEventListener(name, (e) => {
          if (e.lastEventId) lastId = e.lastEventId;
          handlers[name](e);
        });
      });
    }

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible' && es && es.readyState !== EventSource.OPEN) {
        es.close();
        open();
      }
    });

    open();
  }

  // ---------------------------------------------------------------
  // SSE: sürücü lenti (bölmə 7.2, 11.5) — yeni elan üstə düşür, bağlanan sönür (≤3s).
  // ---------------------------------------------------------------
  const feedList = document.getElementById('feed-list');
  if (feedList && body.dataset.role === 'driver' && 'EventSource' in window) {
    const params = new URLSearchParams(window.location.search);
    const currentScope = params.get('tab') === 'intercity' ? 'intercity' : 'baku';
    // Səhifə render olunanda mövcud olan son hadisə ID-dən başlayır — əvvəlki (artıq
    // göstərilmiş) hadisələr "yeni" kimi təkrar oynadılmır.
    const initialLastId = feedList.dataset.lastEventId || '0';

    // FAZA 29: bütün yeni elanlar scope-dan asılı olmadan DƏRHAL lentin başında
    // göstərilir (əvvəlki versiya `scope !== currentScope` olanda kartı tamamilə
    // gizlədirdi — bildiriş bütün sürücülərə gedir, amma lent yalnız cari taba
    // uyğun elanı göstərirdi, nəticədə "bildiriş gəlir, kart görünmür" effekti
    // yaranırdı). İndi uyğunsuz scope-lu kart da dərhal görünür, üstündə hansı
    // bölgəyə aid olduğunu göstərən çip var ki, sürücü qarışdırmasın.
    const SCOPE_LABELS = { baku: 'Bakı daxili', intercity: 'Bölgələrarası' };
    // Digər SSE-üzərindən yazılan mətnlər kimi (bax SCOPE_LABELS) tərcümə olunmur —
    // client tərəfdə i18n mexanizmi yoxdur, server-render olunan versiya (bax
    // partials/listing_card.php, routes.match_badge) düzgün dildə görünür.
    const matchBadgeText = 'Sənin marşrutuna uyğun';

    // Q-Y12: marşrut abunəliyinə uyğun elanı vurğulamaq üçün — eyni məntiq
    // App\Core\ListingRules::matchesAnySubscription()-un JS dublikatıdır (SSE ilə
    // canlı gələn kartlar üçün, çünki 'feed' kanalı bütün sürücülərə eyni HTML
    // göndərir, server-tərəfdə fərdiləşdirilə bilmir).
    let routeSubscriptions = [];
    try {
      routeSubscriptions = JSON.parse(feedList.dataset.routeSubscriptions || '[]');
    } catch (err) {
      routeSubscriptions = [];
    }
    const matchesAnySubscription = (payload) => {
      return routeSubscriptions.some((s) => {
        if (s.scope !== 'all' && s.scope !== payload.scope) return false;
        if (s.from_location_id !== null && Number(s.from_location_id) !== Number(payload.from_location_id)) return false;
        if (s.to_location_id !== null && Number(s.to_location_id) !== Number(payload.to_location_id)) return false;
        return true;
      });
    };

    // FAZA 20: kartın HTML-i artıq SSE hadisəsinin İÇİNDƏ gəlir (bax
    // ListingRules::renderFeedCard()) — əvvəlki dizaynda bu funksiya ayrıca
    // fetch('/surucu/lent/kart/'+id) çağırırdı, bu əlavə round-trip özü müstəqil
    // uğursuzluq nöqtəsi idi (hadisə çatsa da, sonrakı sorğu uğursuz/gec olarsa kart
    // heç görünmürdü). İndi heç bir şəbəkə sorğusu lazım deyil, sırf DOM əlavəsi.
    const handleListingNew = (e) => {
      const data = JSON.parse(e.data);
      const listingId = data.payload.listing_id;
      if (!data.payload.html) {
        return;
      }
      if (feedList.querySelector('[data-listing-id="' + listingId + '"]')) {
        return;
      }
      const wrapper = document.createElement('div');
      wrapper.innerHTML = data.payload.html.trim();
      const el = wrapper.firstChild;

      if (data.payload.scope && data.payload.scope !== currentScope) {
        const chipRow = el.children[1];
        if (chipRow) {
          const scopeChip = document.createElement('span');
          scopeChip.className = 'chip chip-warn';
          scopeChip.textContent = SCOPE_LABELS[data.payload.scope] || data.payload.scope;
          chipRow.prepend(scopeChip);
        }
      }

      if (routeSubscriptions.length > 0 && matchesAnySubscription(data.payload)) {
        el.style.borderColor = 'var(--primary)';
        const chipRow = el.children[1];
        if (chipRow) {
          const matchChip = document.createElement('span');
          matchChip.className = 'chip chip-match';
          matchChip.textContent = matchBadgeText;
          chipRow.prepend(matchChip);
        }
      }

      // Giriş animasiyası CSS-dəki `.card { animation: cardIn ... }` qaydası ilə
      // avtomatik işə düşür (app.birlikde.biz-in bounce ritminə uyğun) — burada əlavə
      // inline stil lazım deyil, ikiqat animasiya toqquşmasının qarşısı alınır.
      feedList.prepend(el);
    };

    const removeCard = (listingId) => {
      const el = feedList.querySelector('[data-listing-id="' + listingId + '"]');
      if (!el) {
        return;
      }
      el.style.transition = 'opacity .3s ease, transform .3s ease';
      el.style.opacity = '0';
      el.style.transform = 'scale(0.96)';
      setTimeout(() => el.remove(), 300);
    };

    connectResilientSSE('/axin/lent', initialLastId, {
      listing_new: handleListingNew,
      listing_reopened: handleListingNew,
      listing_closed: (e) => {
        const data = JSON.parse(e.data);
        removeCard(data.payload.listing_id);
      },
      offer_accepted: () => {
        // Bu sürücünün təklifi qəbul olunub — "Təkliflərim" səhifəsi növbəti ziyarətdə yenilənəcək.
      },
    });
  }

  // ---------------------------------------------------------------
  // SSE: müştəri elan səhifəsi — yeni təklif/qəbul zamanı avtomatik yenilənir.
  // ---------------------------------------------------------------
  const listingContainer = document.querySelector('[data-listing-page]');
  if (listingContainer && body.dataset.role !== 'driver' && 'EventSource' in window) {
    const myListingId = listingContainer.dataset.listingPage;
    const initialLastId2 = listingContainer.dataset.lastEventId || '0';
    const onListingEvent = (e) => {
      const data = JSON.parse(e.data);
      if (String(data.payload.listing_id) === String(myListingId)) {
        window.location.reload();
      }
    };
    connectResilientSSE('/axin/musteri', initialLastId2, {
      offer_new: onListingEvent,
      offer_updated: onListingEvent,
      accepted: onListingEvent,
    });
  }

  // ---------------------------------------------------------------
  // Banner karuseli — hər 2 saniyədən bir avtomatik növbəti banner (bax
  // partials/banner_carousel.php); yalnız birdən çox banner olduqda işə düşür.
  // ---------------------------------------------------------------
  // ---------------------------------------------------------------
  // Foto yükləmə önbaxışı (FAZA 14) — seçilən şəkillərin kiçik thumbnail-ları
  // upload-tile altında göstərilir (əvvəllər yalnız "N şəkil seçildi" mətni var idi).
  // ---------------------------------------------------------------
  document.querySelectorAll('input[type="file"][data-photos-cta]').forEach((input) => {
    const label = input.id ? document.getElementById(input.id + '_text') : null;
    const preview = input.id ? document.getElementById(input.id + '_preview') : null;
    input.addEventListener('change', () => {
      const n = input.files.length;
      if (label) {
        label.textContent = n ? n + ' ' + input.dataset.photosSelected : input.dataset.photosCta;
      }
      if (preview) {
        preview.innerHTML = '';
        Array.from(input.files).forEach((file) => {
          const img = document.createElement('img');
          img.src = URL.createObjectURL(file);
          img.alt = '';
          preview.appendChild(img);
        });
      }
    });
  });

  document.querySelectorAll('[data-banner-carousel]').forEach((carousel) => {
    const slides = carousel.querySelectorAll('.banner-slide');
    const dots = carousel.querySelectorAll('.banner-dot');
    if (slides.length < 2) return;
    let idx = 0;
    setInterval(() => {
      slides[idx].classList.remove('active');
      dots[idx]?.classList.remove('active');
      idx = (idx + 1) % slides.length;
      slides[idx].classList.add('active');
      dots[idx]?.classList.add('active');
    }, 2000);
  });

  // ---------------------------------------------------------------
  // Şəkil lightbox-u — elan şəkillərinin kiçik zolağında (.photo-strip) toxunanda
  // tam ekran böyüdülmüş göstərilir (bax partials/photo_lightbox.php).
  // ---------------------------------------------------------------
  const lightbox = document.getElementById('photo-lightbox');
  const lightboxImg = document.getElementById('photo-lightbox-img');
  if (lightbox && lightboxImg) {
    document.querySelectorAll('.photo-strip img').forEach((img) => {
      img.addEventListener('click', () => {
        lightboxImg.src = img.src;
        lightbox.hidden = false;
      });
    });
    const closeLightbox = () => { lightbox.hidden = true; lightboxImg.src = ''; };
    document.getElementById('photo-lightbox-close')?.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) closeLightbox();
    });
  }
})();
