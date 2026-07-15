(() => {
  const state = { stream: null, wakeLock: null, track: null, detector: null, scanning: false, submitting: false };
  const $ = (selector) => document.querySelector(selector);

  function showScannerMessage(message) {
    const el = $('[data-scanner-message]');
    if (el) el.textContent = message;
  }

  async function keepAwake() {
    try {
      if ('wakeLock' in navigator) state.wakeLock = await navigator.wakeLock.request('screen');
    } catch (_) {}
  }

  async function prepareDetector() {
    if (!('BarcodeDetector' in window)) {
      showScannerMessage('Cámara activa. Si tu navegador no detecta QR automáticamente, pega el token manualmente.');
      return null;
    }
    if (!state.detector) state.detector = new BarcodeDetector({ formats: ['qr_code'] });
    return state.detector;
  }

  async function startScanner() {
    if (!navigator.onLine) return showScannerMessage('Sin conexión. No es posible validar esta entrada.');
    if (!navigator.mediaDevices?.getUserMedia) return showScannerMessage('Este navegador no permite abrir la cámara. Usa HTTPS o localhost.');

    try {
      state.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
      state.track = state.stream.getVideoTracks()[0];
      const video = $('[data-scanner-video]');
      if (video) {
        video.srcObject = state.stream;
        await video.play();
      }
      await keepAwake();
      await prepareDetector();
      state.scanning = true;
      showScannerMessage('Cámara abierta. Apunta al QR de la entrada.');
      scanLoop();
    } catch (error) {
      showScannerMessage('No se pudo abrir la cámara. Revisa permisos del navegador.');
    }
  }

  async function scanLoop() {
    const video = $('[data-scanner-video]');
    if (!state.scanning || !video || !state.detector || state.submitting) {
      if (state.scanning) requestAnimationFrame(scanLoop);
      return;
    }

    try {
      const codes = await state.detector.detect(video);
      const rawValue = codes?.[0]?.rawValue;
      if (rawValue) submitScan(rawValue);
    } catch (_) {}

    if (state.scanning) requestAnimationFrame(scanLoop);
  }

  function submitScan(payload) {
    if (!navigator.onLine) {
      showScannerMessage('Sin conexión. No es posible validar esta entrada.');
      navigator.vibrate?.([80, 40, 80]);
      return false;
    }

    const input = $('[data-scanner-input]');
    const form = $('[data-scanner-form]');
    if (!input || !form) return false;
    input.value = payload.trim();
    state.submitting = true;
    navigator.vibrate?.(80);
    showScannerMessage('QR detectado. Validando entrada…');
    form.submit();
    return true;
  }

  async function toggleTorch() {
    const caps = state.track?.getCapabilities?.();
    if (!caps?.torch) return showScannerMessage('La linterna no está disponible en este dispositivo.');
    const enabled = !state.track.__torchEnabled;
    await state.track.applyConstraints({ advanced: [{ torch: enabled }] });
    state.track.__torchEnabled = enabled;
    showScannerMessage(enabled ? 'Linterna encendida.' : 'Linterna apagada.');
  }

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-scanner-start]')) startScanner();
    if (event.target.closest('[data-scanner-torch]')) toggleTorch();
  });
  window.addEventListener('offline', () => showScannerMessage('Sin conexión. No es posible validar esta entrada.'));
  window.addEventListener('pagehide', () => state.stream?.getTracks().forEach((track) => track.stop()));
  window.FiestaScanner = { startScanner, toggleTorch, submitScan };
})();
