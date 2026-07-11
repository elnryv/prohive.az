const CACHE_NAME = 'birlikde-shell-v1';
const APP_SHELL = [
  '/manifest.json',
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
    )
  );
  self.clients.claim();
});

/**
 * Bax CLAUDE.md bölmə 9.2: app shell cache-first, SSE network-only.
 * DİQQƏT: yalnız /assets/* və manifest.json kimi HƏQİQƏTƏN statik, istifadəçidən
 * asılı olmayan fayllar tutulur. HTML səhifələri və JSON API sorğuları QƏSDƏN
 * toxunulmur (event.respondWith çağırılmır) — əks halda brauzerin service worker
 * daxilində fetch(event.request) çağırışı credentials-i itirə bilər (məlum
 * Chromium davranışı) və sessiya cookie-si API sorğularına getməz.
 */
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  if (event.request.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  const isStaticAsset = url.pathname.startsWith('/assets/') || url.pathname === '/manifest.json';
  if (!isStaticAsset) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cached) => {
      const networkFetch = fetch(event.request)
        .then((response) => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          }
          return response;
        })
        .catch(() => cached);

      return cached || networkFetch;
    })
  );
});

// Web Push qəbulu (göndərmə tərəfi YER TUTUCUDUR — bax PushService qeydi).
self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = {};
  }

  event.waitUntil(
    self.registration.showNotification(data.title || 'Birlikdə', {
      body: data.body || '',
      icon: '/assets/icons/icon-192.png',
      badge: '/assets/icons/icon-192.png',
      data: { url: data.url || '/' },
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/';
  event.waitUntil(self.clients.openWindow(url));
});
