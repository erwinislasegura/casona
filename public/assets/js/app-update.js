(() => {
  let waitingWorker = null;
  function createToast() {
    const toast = document.createElement('div');
    toast.className = 'app-update-toast';
    toast.innerHTML = '<p>Hay una nueva versión disponible.</p><button class="app-update-now" type="button">Actualizar ahora</button><button class="app-update-later" type="button">Más tarde</button>';
    document.body.appendChild(toast);
    toast.querySelector('.app-update-now').addEventListener('click', () => {
      if (document.querySelector('form[data-dirty="true"]') && !confirm('Hay formularios sin enviar. ¿Actualizar de todos modos?')) return;
      waitingWorker?.postMessage({ type: 'SKIP_WAITING' });
    });
    toast.querySelector('.app-update-later').addEventListener('click', () => toast.classList.remove('is-visible'));
    return toast;
  }
  const toast = () => document.querySelector('.app-update-toast') || createToast();
  window.addEventListener('pwa:registered', (event) => {
    const registration = event.detail;
    function watch(worker) {
      if (!worker) return;
      worker.addEventListener('statechange', () => {
        if (worker.state === 'installed' && navigator.serviceWorker.controller) { waitingWorker = worker; toast().classList.add('is-visible'); }
      });
    }
    watch(registration.installing);
    registration.addEventListener('updatefound', () => watch(registration.installing));
  });
  let refreshing = false;
  navigator.serviceWorker?.addEventListener('controllerchange', () => { if (!refreshing) { refreshing = true; location.reload(); } });
  document.addEventListener('input', (event) => { const form = event.target.closest('form'); if (form) form.dataset.dirty = 'true'; });
  document.addEventListener('submit', (event) => { event.target.dataset.dirty = 'false'; }, true);
})();
