<?php
$basePath = $basePath ?? '';
$appVersion = $appVersion ?? '1.0.0';
$message = $message ?? 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0b1020">
  <link rel="manifest" href="<?= $basePath ?>/manifest.webmanifest">
  <link rel="apple-touch-icon" href="<?= $basePath ?>/assets/logo-ciclon.jpeg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9Oer+R4F0S3pHCFWhT6+K6nvctHf1Ra9sENBo0LRn5q+8" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/pwa.css">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/login.css">
  <title>Recuperar contraseña · Fiesta Ochentera</title>
</head>
<body class="login-body">
  <main class="container py-3 d-grid" style="min-height:100svh;place-items:center">
    <section class="login-card card border-0 shadow-lg">
      <div class="card-body p-3 p-sm-4">
        <div class="logo-row justify-content-center mb-3"><span class="logo-badge"><img src="<?= $basePath ?>/assets/logo-ciclon.jpeg" alt="Ciclón Producciones"></span></div>
        <span class="kicker">Recuperación</span>
        <h1 class="h4 fw-black mt-2 mb-2">Restablecer acceso</h1>
        <p class="restricted small">Ingresa tu correo. No revelaremos si existe una cuenta asociada.</p>
        <div class="alert alert-info py-2 small" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <form method="post" action="<?= $basePath ?>/admin/forgot-password/" class="compact-login-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label for="email" class="form-label small fw-bold mb-1">Correo electrónico</label>
          <input id="email" name="email" type="email" class="form-control form-control-lg mb-3" autocomplete="username" required>
          <button class="btn btn-warning w-100 fw-black py-2" type="submit" data-requires-online>Enviar instrucciones</button>
        </form>
        <div class="text-center mt-3 small"><a class="link" href="<?= $basePath ?>/admin/login/">Volver al login</a></div>
        <footer class="login-footer mt-3 small">Versión <?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></footer>
      </div>
    </section>
  </main>
  <script src="<?= $basePath ?>/assets/js/connection-status.js" defer></script>
</body>
</html>
