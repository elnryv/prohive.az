// Birlikdə Getdik — Service Worker (bölmə 11.2)
// Strategiya:
//   - App shell (css/js/loqo/offline/manifest): cache-first, versiyalı.
//   - Səhifə HTML-ləri: network-first → offline-da offline.html.
//   - Ev fotoları (/uploads/houses/...): stale-while-revalidate, maks 60 şəkil (LRU).
//   - API (track, sse, callback): heç vaxt keşlənmir.

const CACHE_VERSION = 'getdik-v1';
const SHELL_CACHE = CACHE_VERSION + '-shell';
const PHOTO_CACHE = CACHE_VERSION + '-photos';
const PHOTO_CACHE_MAX = 60;

const SHELL_ASSETS = [
    '/assets/css/app.css',
    '/assets/js/app.js',
    '/offline.html',
    '/manifest.webmanifest',
    '/assets/icons/icon-192.png',
    '/assets/icons/icon-512.png',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(SHELL_CACHE)
            .then(function (cache) { return cache.addAll(SHELL_ASSETS); })
            .then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then(function (keys) {
                return Promise.all(
                    keys
                        .filter(function (k) { return k.indexOf('getdik-') === 0 && k !== SHELL_CACHE && k !== PHOTO_CACHE; })
                        .map(function (k) { return caches.delete(k); })
                );
            })
            .then(function () { return self.clients.claim(); })
    );
});

function isNeverCached(pathname) {
    return pathname.indexOf('/api/') === 0 || pathname === '/sse' || pathname === '/odenis/callback';
}

function isPhoto(pathname) {
    return pathname.indexOf('/uploads/houses/') === 0;
}

function isShellAsset(pathname) {
    return SHELL_ASSETS.indexOf(pathname) !== -1;
}

function trimCache(cacheName, maxItems) {
    caches.open(cacheName).then(function (cache) {
        cache.keys().then(function (keys) {
            if (keys.length > maxItems) {
                cache.delete(keys[0]).then(function () {
                    trimCache(cacheName, maxItems);
                });
            }
        });
    });
}

self.addEventListener('fetch', function (event) {
    const req = event.request;
    if (req.method !== 'GET') {
        return;
    }
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (isNeverCached(url.pathname)) {
        return; // birbaşa şəbəkəyə, keşə toxunulmur
    }

    if (isPhoto(url.pathname)) {
        event.respondWith(
            caches.open(PHOTO_CACHE).then(function (cache) {
                return cache.match(req).then(function (cached) {
                    const networkFetch = fetch(req).then(function (res) {
                        if (res && res.ok) {
                            cache.put(req, res.clone());
                            trimCache(PHOTO_CACHE, PHOTO_CACHE_MAX);
                        }
                        return res;
                    }).catch(function () { return cached; });
                    return cached || networkFetch;
                });
            })
        );
        return;
    }

    if (isShellAsset(url.pathname)) {
        event.respondWith(
            caches.match(req).then(function (cached) { return cached || fetch(req); })
        );
        return;
    }

    const acceptsHtml = req.headers.get('accept') && req.headers.get('accept').indexOf('text/html') !== -1;
    if (req.mode === 'navigate' || acceptsHtml) {
        event.respondWith(
            fetch(req).catch(function () { return caches.match('/offline.html'); })
        );
    }
});
