(() => {
  let deferredPrompt = null;
  const buttons = () => document.querySelectorAll('[data-install-pwa]');
  const isiOS = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  const isStandalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone;
  function showInstallButtons(show) { buttons().forEach((button) => button.classList.toggle('is-visible', show)); }
  window.addEventListener('beforeinstallprompt', (event) => { event.preventDefault(); deferredPrompt = event; showInstallButtons(!isStandalone); });
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-install-pwa]');
    if (!button || !deferredPrompt) return;
    deferredPrompt.prompt();
    await deferredPrompt.userChoice.catch(() => null);
    deferredPrompt = null; showInstallButtons(false);
  });
  window.addEventListener('appinstalled', () => showInstallButtons(false));
  document.addEventListener('DOMContentLoaded', () => {
    showInstallButtons(Boolean(deferredPrompt) && !isStandalone);
    document.querySelectorAll('[data-ios-install-hint]').forEach((hint) => hint.classList.toggle('is-visible', isiOS && !isStandalone));
  });
})();
