<div class="admin-panel-section scanner-module is-camera-ready">
  <div>
    <h2>Escáner QR</h2>
    <p>Abre la cámara trasera, detecta el QR y envía el token automáticamente para validar la entrada.</p>
    <?php if ($scannerResult): ?><div class="scanner-result <?= $scannerResult['ok'] ? 'is-ok' : 'is-error' ?>"><?= htmlspecialchars($scannerResult['message'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <div class="scanner-help" data-scanner-message>Presiona “Abrir cámara” y apunta al código QR.</div>
  </div>
  <form method="post" class="scanner-card" data-scanner-form>
    <input type="hidden" name="action" value="validate_ticket">
    <div class="scanner-camera-box">
      <video data-scanner-video playsinline muted></video>
      <div class="scanner-reticle" aria-hidden="true"></div>
    </div>
    <label class="w-100"><span class="visually-hidden">Token QR</span><input class="form-control" name="qr_token" data-scanner-input placeholder="Token detectado o pegado manualmente" required></label>
    <div class="scanner-controls">
      <button class="admin-primary-button" type="button" data-scanner-start>Abrir cámara</button>
      <button class="admin-secondary-button" type="button" data-scanner-torch>Linterna</button>
      <button class="admin-secondary-button" type="submit">Validar manual</button>
    </div>
  </form>
</div>
