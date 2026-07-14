(() => {
  const state = { stream: null, wakeLock: null, track: null };
  const $ = (s) => document.querySelector(s);
  async function keepAwake() { try { if ('wakeLock' in navigator) state.wakeLock = await navigator.wakeLock.request('screen'); } catch (_) {} }
  async function startScanner() {
    if (!navigator.onLine) return showScannerMessage('Sin conexión. No es posible validar esta entrada');
    state.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
    state.track = state.stream.getVideoTracks()[0];
    const video = $('[data-scanner-video]'); if (video) { video.srcObject = state.stream; await video.play(); }
    keepAwake();
  }
  async function toggleTorch() {
    const caps = state.track?.getCapabilities?.();
    if (!caps?.torch) return showScannerMessage('La linterna no está disponible en este dispositivo.');
    const enabled = !state.track.__torchEnabled; await state.track.applyConstraints({ advanced: [{ torch: enabled }] }); state.track.__torchEnabled = enabled;
  }
  function showScannerMessage(message) { const el = $('[data-scanner-message]'); if (el) el.textContent = message; }
  function validateScan(payload) {
    if (!navigator.onLine) { showScannerMessage('Sin conexión. No es posible validar esta entrada'); navigator.vibrate?.([80,40,80]); return false; }
    return true;
  }
  window.FiestaScanner = { startScanner, toggleTorch, validateScan };
  window.addEventListener('offline', () => showScannerMessage('Sin conexión. No es posible validar esta entrada'));
})();
