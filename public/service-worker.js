const PWA_VERSION = 'fiesta-80s-v1.0.1';
const STATIC_CACHE = `${PWA_VERSION}-static`;
const PAGE_CACHE = `${PWA_VERSION}-pages`;
const BASE_PATH = new URL(self.registration.scope).pathname.replace(/\/$/, '');
const withBase = (path) => `${BASE_PATH}${path}`;
const OFFLINE_URL = withBase('/offline.html');
const STATIC_ASSETS = [
  OFFLINE_URL,
  withBase('/manifest.webmanifest'),
  withBase('/assets/css/login.css'),
  withBase('/assets/css/pwa.css'),
  withBase('/assets/js/install-pwa.js'),
  withBase('/assets/js/service-worker-register.js'),
  withBase('/assets/js/app-update.js'),
  withBase('/assets/js/connection-status.js'),
  withBase('/assets/js/scanner.v1.js'),
  withBase('/assets/logo-san-gabriel.png'),withBase('/assets/logo-ciclon.jpeg'),withBase('/assets/logo-la-casona.jpeg'),withBase('/assets/abba-color-1.png'),withBase('/assets/abba-color-2.png'),withBase('/assets/abba-color-3.png'),withBase('/assets/abba.png'),withBase('/assets/abba-cta.jpg')
];
const SENSITIVE_PATTERNS = [
  /(?:^|\/)admin\/api\//, /(?:^|\/)admin\/reservas\//, /(?:^|\/)admin\/entradas\//, /(?:^|\/)admin\/usuarios\//,
  /(?:^|\/)admin\/configuracion\//, /(?:^|\/)storage\//, /comprobante/i, /pdf/i, /token/i, /session/i,
  /(?:^|\/)admin\/login\/?(?:\?.*)?$/
];
const STATIC_PATTERN = /\.(?:css|js|png|jpg|jpeg|webp|svg|ico|woff2?)$/i;
self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => ![STATIC_CACHE, PAGE_CACHE].includes(key)).map((key) => caches.delete(key)))).then(() => self.clients.claim()));
});
self.addEventListener('message', (event) => { if (event.data?.type === 'SKIP_WAITING') self.skipWaiting(); });
function isSensitive(request, url) {
  if (request.method !== 'GET') return true;
  if (url.origin !== self.location.origin) return true;
  return SENSITIVE_PATTERNS.some((pattern) => pattern.test(url.pathname + url.search));
}
async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  const response = await fetch(request);
  if (response.ok) (await caches.open(STATIC_CACHE)).put(request, response.clone());
  return response;
}
async function networkFirstPage(request) {
  try {
    const response = await fetch(request);
    if (response.ok && response.type === 'basic') (await caches.open(PAGE_CACHE)).put(request, response.clone());
    return response;
  } catch (_) {
    return (await caches.match(request)) || caches.match(OFFLINE_URL);
  }
}
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  if (isSensitive(request, url)) return;
  if (STATIC_PATTERN.test(url.pathname)) event.respondWith(cacheFirst(request));
  else if (request.mode === 'navigate') event.respondWith(networkFirstPage(request));
});
