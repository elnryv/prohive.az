// Birlikdə Getdik — Service Worker
// Faza 0: qeydiyyat üçün boş skelet. Tam keşləmə strategiyası (bölmə 11.2) Faza 5-də əlavə olunacaq.
const CACHE_VERSION = 'getdik-v0';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});
