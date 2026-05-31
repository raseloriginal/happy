// DSR PWA Service Worker - v6
// IMPORTANT: Never cache PHP pages or navigations. Only cache static assets.
const CACHE_NAME = 'dsr-static-cache-v6';

// Only cache truly static assets (images, fonts, manifest)
const STATIC_ASSETS = [
  './manifest.json',
  '../assets/img/logo/logo-black.png',
  '../assets/img/logo/logo-icon-black.png',
  '../assets/img/logo/logo.png',
  '../assets/img/logo/logo-icon.png',
  '../assets/img/logo/pwa-icon-192.png',
  '../assets/img/logo/pwa-icon-512.png'
];

// On install: skip waiting immediately and only cache static assets
self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      // Use individual adds so one failure doesn't break everything
      return Promise.allSettled(STATIC_ASSETS.map(url => cache.add(url)));
    })
  );
});

// On activate: delete ALL old caches and claim clients immediately
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          // Delete every cache that isn't our current one
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      return self.clients.claim();
    })
  );
});

// On fetch: NEVER intercept navigation requests (PHP pages, redirects, etc.)
self.addEventListener('fetch', event => {
  const req = event.request;

  // Skip non-GET requests
  if (req.method !== 'GET') return;

  // Skip non-http(s) requests
  if (!req.url.startsWith('http')) return;

  // CRITICAL: Never intercept navigation — let PHP handle all page loads
  if (req.mode === 'navigate') return;

  // Skip API calls — always fresh from network
  if (req.url.includes('/api/')) return;

  // Skip PHP files — never serve from cache
  if (req.url.includes('.php')) return;

  // For remaining static assets: cache-first strategy
  event.respondWith(
    caches.match(req).then(cached => {
      if (cached) return cached;

      return fetch(req).then(response => {
        // Only cache valid, same-origin responses
        if (response && response.status === 200 && response.type === 'basic') {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(req, responseToCache);
          });
        }
        return response;
      }).catch(() => {
        // Network failed and no cache — just fail silently
      });
    })
  );
});
