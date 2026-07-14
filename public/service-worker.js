const PWA_VERSION = 'fiesta-80s-v1.0.0';
const STATIC_CACHE = `${PWA_VERSION}-static`;
const PAGE_CACHE = `${PWA_VERSION}-pages`;
const OFFLINE_URL = '/offline.html';
const STATIC_ASSETS = [
  OFFLINE_URL,
  '/manifest.webmanifest',
  '/assets/css/login.css',
  '/assets/css/pwa.css',
  '/assets/js/install-pwa.js',
  '/assets/js/service-worker-register.js',
  '/assets/js/app-update.js',
  '/assets/js/connection-status.js',
  '/assets/js/scanner.v1.js',
  '/assets/logo-san-gabriel.png','/assets/logo-ciclon.jpeg','/assets/logo-la-casona.jpeg','/assets/abba-color-1.png','/assets/abba-color-2.png','/assets/abba-color-3.png','/assets/abba.png','/assets/abba-cta.jpg'
];
const SENSITIVE_PATTERNS = [
  /^\/admin\/api\//, /^\/admin\/reservas\//, /^\/admin\/entradas\//, /^\/admin\/usuarios\//,
  /^\/admin\/configuracion\//, /^\/storage\//, /comprobante/i, /pdf/i, /token/i, /session/i,
  /^\/admin\/login$/
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
