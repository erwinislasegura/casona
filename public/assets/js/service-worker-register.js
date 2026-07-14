(() => {
  if (!('serviceWorker' in navigator)) return;
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
      .then((registration) => window.dispatchEvent(new CustomEvent('pwa:registered', { detail: registration })))
      .catch((error) => console.warn('No fue posible registrar el Service Worker.', error));
  });
})();
