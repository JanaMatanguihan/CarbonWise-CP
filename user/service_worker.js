// CarbonWise Service Worker
// ---------------------------------------------------------------
// Strategy:
//   - Static assets (css, images, fonts, icons)  -> cache-first
//   - Everything else (all .php pages, API calls) -> network-first,
//     NEVER served from cache when offline.
//
// This last point matters specifically for this app: location_gate.php,
// verify_location.php, login.php, index.php and every campus-gated page
// must always hit the real server so the geofence check in
// require_campus.php actually runs. If these were cached, a device could
// show a previously-cached "verified" page while offline/off-campus,
// silently bypassing the location check. So: PHP pages are NEVER cached.
// ---------------------------------------------------------------

const CACHE_NAME = 'carbonwise-static-v2';

// Only static, non-sensitive assets go in the precache.
const STATIC_ASSETS = [
  'manifest.json',
  'style.css',
  'logo.png',
  'icons/icon-192.png',
  'icons/icon-512.png',
  'icons/icon-maskable-192.png',
  'icons/icon-maskable-512.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      )
    )
  );
  self.clients.claim();
});

// True for any request we must NEVER serve from cache or save to cache:
// all .php endpoints (pages + the location/auth APIs), and anything
// that isn't a simple GET.
function isNeverCache(request, url) {
  if (request.method !== 'GET') return true;
  if (url.pathname.endsWith('.php')) return true;
  if (url.pathname.includes('verify_location') || url.pathname.includes('location_gate')) return true;
  return false;
}

function isStaticAsset(url) {
  return /\.(css|js|png|jpg|jpeg|svg|webp|woff2?|ttf)$/i.test(url.pathname);
}

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Only handle same-origin requests; let cross-origin (CDN fonts/scripts,
  // Brevo, etc.) pass straight through to the network untouched.
  if (url.origin !== self.location.origin) return;

  if (isNeverCache(event.request, url)) {
    // Network-first, no fallback to cache. If offline, the request fails
    // normally (the page's own fetch error handling takes over) instead
    // of silently serving a stale "verified" or "logged in" state.
    event.respondWith(fetch(event.request));
    return;
  }

  if (isStaticAsset(url)) {
    // Cache-first for static assets, with a background network update.
    event.respondWith(
      caches.match(event.request).then(cached => {
        const networkFetch = fetch(event.request)
          .then(response => {
            if (response && response.ok) {
              const clone = response.clone();
              caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
            }
            return response;
          })
          .catch(() => cached); // offline: fall back to whatever's cached

        return cached || networkFetch;
      })
    );
    return;
  }

  // Default: just go to the network.
  event.respondWith(fetch(event.request));
});