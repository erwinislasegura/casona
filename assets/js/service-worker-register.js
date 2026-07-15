(() => {
  if (!('serviceWorker' in navigator)) return;
  window.addEventListener('load', () => {
    const base = document.querySelector('link[rel="manifest"]')?.href
      ? new URL('.', document.querySelector('link[rel="manifest"]').href).pathname
      : new URL('../..', document.currentScript?.src || window.location.href).pathname;
    const scope = base.endsWith('/') ? base : `${base}/`;
    navigator.serviceWorker.register(`${scope}service-worker.js`, { scope })
      .then((registration) => window.dispatchEvent(new CustomEvent('pwa:registered', { detail: registration })))
      .catch((error) => console.warn('No fue posible registrar el Service Worker.', error));
  });
})();
