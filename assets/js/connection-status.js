(() => {
  function update() {
    const offline = !navigator.onLine;
    document.documentElement.classList.toggle('is-offline', offline);
    document.querySelectorAll('[data-connection-status]').forEach((el) => { el.classList.toggle('is-offline', offline); el.textContent = offline ? 'Sin conexión' : 'En línea'; });
    document.querySelectorAll('[data-requires-online]').forEach((el) => { el.toggleAttribute('disabled', offline); });
  }
  window.addEventListener('online', update); window.addEventListener('offline', update); document.addEventListener('DOMContentLoaded', update);
})();
