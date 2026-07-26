// Service Worker — Hissə 2.6. Build addımı olmadığı üçün CACHE_VERSION əl ilə
// artırılır (hər deploy-da) ki, köhnə keş təmizlənsin və yeniləmə toast-ı görünsün.
const CACHE_VERSION = 'ybb-v1';
const PRECACHE = `${CACHE_VERSION}-precache`;
const RUNTIME_API = `${CACHE_VERSION}-api`;

const PRECACHE_URLS = [
  '/',
  '/manifest.json',
  '/offline.html',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
  '/assets/css/tokens.css',
  '/assets/css/components.css',
  '/assets/css/screens/splash.css',
  '/assets/css/screens/phone.css',
  '/assets/css/screens/pin.css',
  '/assets/css/screens/register.css',
  '/assets/css/screens/home.css',
  '/assets/js/app.js',
  '/assets/js/router.js',
  '/assets/js/api.js',
  '/assets/js/sse.js',
  '/assets/js/store.js',
  '/assets/js/utils.js',
  '/assets/js/notify-permission.js',
  '/assets/js/install-prompt.js',
  '/assets/js/components/appbar.js',
  '/assets/js/components/banner.js',
  '/assets/js/components/bottomnav.js',
  '/assets/js/components/card.js',
  '/assets/js/components/chip.js',
  '/assets/js/components/empty.js',
  '/assets/js/components/offer-sheet.js',
  '/assets/js/components/pinpad.js',
  '/assets/js/components/sheet.js',
  '/assets/js/components/skeleton.js',
  '/assets/js/components/stepper.js',
  '/assets/js/components/toast.js',
  '/assets/js/screens/splash.js',
  '/assets/js/screens/phone.js',
  '/assets/js/screens/pin.js',
  '/assets/js/screens/register.js',
  '/assets/js/screens/home.js',
  '/assets/js/screens/order-create.js',
  '/assets/js/screens/order-detail.js',
  '/assets/js/views/customer-home.js',
  '/assets/js/views/driver-feed.js',
  '/assets/js/views/my-orders.js',
  '/assets/js/views/my-offers.js',
  '/assets/js/views/notifications-view.js',
  '/assets/js/views/profile-view.js',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(PRECACHE).then((cache) => Promise.allSettled(
      // addAll() would fail atomically if a single URL 404s (e.g. a font not
      // yet shipped) — cache what we can instead of losing the whole install.
      PRECACHE_URLS.map((url) => cache.add(url))
    ))
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key.startsWith('ybb-') && key !== PRECACHE && key !== RUNTIME_API)
        .map((key) => caches.delete(key))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  if (request.method !== 'GET' || url.pathname.startsWith('/sse/')) {
    return; // mutasiyalar və SSE axını SW-dən keçmir
  }

  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkOnlyWithCacheFallback(request));
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(networkFirstShell(request));
    return;
  }

  event.respondWith(cacheFirst(request));
});

async function networkOnlyWithCacheFallback(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(RUNTIME_API);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    return cached ?? new Response(JSON.stringify({ ok: false, error: { code: 'OFFLINE', message: 'İnternet bağlantısı yoxdur.' } }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' },
    });
  }
}

async function networkFirstShell(request) {
  try {
    return await fetch(request);
  } catch {
    return (await caches.match('/')) ?? (await caches.match('/offline.html'));
  }
}

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) {
    return cached;
  }
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(PRECACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return cached ?? Response.error();
  }
}
