const CACHE_NAME = 'dsr-pwa-cache-v5';

// Only cache truly static assets — never cache PHP pages that require auth
const urlsToCache = [
  './manifest.json',
  '../assets/img/logo/logo-black.png',
  '../assets/img/logo/logo-icon-black.png',
  '../assets/img/logo/logo.png',
  '../assets/img/logo/logo-icon.png',
  '../assets/img/logo/pwa-icon-192.png',
  '../assets/img/logo/pwa-icon-512.png'
];

self.addEventListener('install', event => {
  // Skip waiting so the new SW activates immediately
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      // Use individual adds so one missing asset doesn't break the whole install
      return Promise.allSettled(
        urlsToCache.map(url => cache.add(url).catch(() => { /* ignore individual failures */ }))
      );
    })
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => name !== CACHE_NAME)
          .map(name => caches.delete(name))
      );
    }).then(() => self.clients.claim()) // Take control immediately
  );
});

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Only handle GET requests
  if (event.request.method !== 'GET') return;

  // Never intercept PHP pages (auth-protected) — let them go straight to server
  // This prevents the SW from caching login redirects or serving stale auth pages
  if (url.pathname.endsWith('.php') || url.pathname.match(/\/(index|login|stock|settlement|expenses)(\/|$)/)) {
    event.respondWith(fetch(event.request));
    return;
  }

  // Never intercept API calls
  if (url.pathname.includes('/api/')) {
    event.respondWith(fetch(event.request));
    return;
  }

  // For static assets: cache-first strategy
  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) return cached;

      return fetch(event.request).then(response => {
        // Only cache valid, same-origin responses
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        const toCache = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(event.request, toCache));
        return response;
      }).catch(() => {
        // Network failed and no cache — return nothing gracefully
        // Returning undefined here is safe because we only reach this for static assets
        return new Response('', { status: 503, statusText: 'Service Unavailable' });
      });
    })
  );
});
