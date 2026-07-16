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
