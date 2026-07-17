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

  if (!isStandalone() && localStorage.getItem(INSTALLED_KEY) !== '1') {
    // Qeydiyyat/giriş bitən kimi (bölmə 6.1): tam ekran sheet, 3 gün cooldown ilə.
    const params = new URLSearchParams(window.location.search);
    if (params.get('xosgeldin') === '1' && !dismissedRecently()) {
      showInstallSheet();
    }
    // Sürücü rolunda hər lent açılışında nazik xatırlatma zolağı (push kritikdir — cooldown-a tabe deyil).
    if (body.dataset.role === 'driver' && document.getElementById('feed-list')) {
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

  if (Notification && Notification.permission === 'granted') {
    subscribeToPush();
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
    const es = new EventSource('/axin/lent?lastId=' + initialLastId);

    es.addEventListener('listing_new', async (e) => {
      const data = JSON.parse(e.data);
      const listingId = data.payload.listing_id;
      try {
        const res = await fetch('/surucu/lent/kart/' + listingId);
        const card = await res.json();
        if (!card.ok || card.scope !== currentScope) {
          return;
        }
        if (feedList.querySelector('[data-listing-id="' + listingId + '"]')) {
          return;
        }
        const wrapper = document.createElement('div');
        wrapper.innerHTML = card.html.trim();
        const el = wrapper.firstChild;
        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px)';
        el.style.transition = 'opacity .3s ease, transform .3s ease';
        feedList.prepend(el);
        requestAnimationFrame(() => {
          el.style.opacity = '1';
          el.style.transform = 'translateY(0)';
        });
      } catch (err) {
        // şəbəkə xətası — növbəti hadisədə yenidən cəhd olunacaq
      }
    });

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

    es.addEventListener('listing_closed', (e) => {
      const data = JSON.parse(e.data);
      removeCard(data.payload.listing_id);
    });

    es.addEventListener('offer_accepted', () => {
      // Bu sürücünün təklifi qəbul olunub — "Təkliflərim" səhifəsi növbəti ziyarətdə yenilənəcək.
    });
  }

  // ---------------------------------------------------------------
  // SSE: müştəri elan səhifəsi — yeni təklif/qəbul zamanı avtomatik yenilənir.
  // ---------------------------------------------------------------
  const listingContainer = document.querySelector('[data-listing-page]');
  if (listingContainer && body.dataset.role !== 'driver' && 'EventSource' in window) {
    const myListingId = listingContainer.dataset.listingPage;
    const initialLastId2 = listingContainer.dataset.lastEventId || '0';
    const es2 = new EventSource('/axin/musteri?lastId=' + initialLastId2);
    ['offer_new', 'offer_updated', 'accepted'].forEach((evt) => {
      es2.addEventListener(evt, (e) => {
        const data = JSON.parse(e.data);
        if (String(data.payload.listing_id) === String(myListingId)) {
          window.location.reload();
        }
      });
    });
  }
})();
