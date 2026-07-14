<?php $appVersion = $appVersion ?? '1.0.0'; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0b1020">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/assets/logo-ciclon.jpeg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9Oer+R4F0S3pHCFWhT6+K6nvctHf1Ra9sENBo0LRn5q+8" crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/css/pwa.css">
  <link rel="stylesheet" href="/assets/css/login.css">
  <title>Nueva contraseña · Fiesta Ochentera</title>
</head>
<body class="login-body">
  <main class="container py-3 d-grid" style="min-height:100svh;place-items:center">
    <section class="login-card card border-0 shadow-lg">
      <div class="card-body p-3 p-sm-4">
        <div class="logo-row justify-content-center mb-3"><span class="logo-badge"><img src="/assets/logo-ciclon.jpeg" alt="Ciclón Producciones"></span></div>
        <span class="kicker">Seguridad</span>
        <h1 class="h4 fw-black mt-2 mb-2">Crear nueva contraseña</h1>
        <form method="post" action="/admin/reset-password" class="compact-login-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label for="password" class="form-label small fw-bold mb-1">Nueva contraseña</label>
          <input id="password" name="password" type="password" class="form-control form-control-lg mb-2" autocomplete="new-password" required>
          <label for="password_confirmation" class="form-label small fw-bold mb-1">Confirmar contraseña</label>
          <input id="password_confirmation" name="password_confirmation" type="password" class="form-control form-control-lg mb-3" autocomplete="new-password" required>
          <button class="btn btn-warning w-100 fw-black py-2" type="submit" data-requires-online>Guardar contraseña</button>
        </form>
        <div class="text-center mt-3 small"><a class="link" href="/admin/login">Volver al login</a></div>
        <footer class="login-footer mt-3 small">Versión <?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></footer>
      </div>
    </section>
  </main>
  <script src="/assets/js/connection-status.js" defer></script>
</body>
</html>
