// Birlikdə Yük — Service Worker (bölmə 11.2). Minimal cache-first strategiya
// statik fayllar üçün + Web Push hadisələri (RFC 8291 aes128gcm serverdə şifrələnir,
// burada sadəcə göstərilir).

const CACHE_NAME = 'yuk-static-v1';
const STATIC_ASSETS = [
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }
  const url = new URL(event.request.url);
  if (!STATIC_ASSETS.includes(url.pathname)) {
    return;
  }
  event.respondWith(
    caches.match(event.request).then((cached) => cached || fetch(event.request))
  );
});

self.addEventListener('push', (e) => {
  let data = {};
  try {
    data = e.data.json();
  } catch (err) {
    data = { title: 'Birlikdə Yük', body: e.data ? e.data.text() : '' };
  }
  e.waitUntil(self.registration.showNotification(data.title || 'Birlikdə Yük', {
    body: data.body || '',
    icon: '/assets/icons/icon-192.png',
    badge: '/assets/icons/badge-72.png',
    data: { url: data.url || '/' },
  }));
});

self.addEventListener('notificationclick', (e) => {
  e.notification.close();
  e.waitUntil(clients.openWindow(e.notification.data.url));
});
