const CACHE_NAME = 'dsr-pwa-cache-v5';
const urlsToCache = [
  './',
  './index',
  './stock',
  './settlement',
  './login',
  './manifest.json',
  '../assets/img/logo/logo-black.png',
  '../assets/img/logo/logo-icon-black.png',
  '../assets/img/logo/logo.png',
  '../assets/img/logo/logo-icon.png',
  '../assets/img/logo/pwa-icon-192.png',
  '../assets/img/logo/pwa-icon-512.png'
];

self.addEventListener('install', event => {
  self.skipWaiting(); // Force the waiting service worker to become the active service worker
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  // Only handle GET requests and http/https URLs to prevent crashes on POST or extension requests
  if (event.request.method !== 'GET' || !event.request.url.startsWith('http')) {
    return;
  }

  // Bypass service worker for document navigation to prevent ERR_FAILED on PHP redirects
  if (event.request.mode === 'navigate') {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response; // Return cached response
        }
        
        // Clone request because it's a stream
        const fetchRequest = event.request.clone();
        
        return fetch(fetchRequest).then(
          response => {
            // Check if we received a valid response
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            
            // Clone the response
            const responseToCache = response.clone();
            
            caches.open(CACHE_NAME)
              .then(cache => {
                // Don't cache API requests dynamically if they change often, but for basic PWA this is ok
                // In production, you might want to exclude /api/ paths from aggressive caching
                if(!event.request.url.includes('/api/')) {
                    cache.put(event.request, responseToCache);
                }
              });
              
            return response;
          }
        ).catch(() => {
            // Optional: return offline fallback page here if network fails
        });
      })
  );
});

self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      return self.clients.claim(); // Take control of all clients immediately
    })
  );
});
