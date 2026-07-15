<?php
$basePath = $basePath ?? '';
$appVersion = $appVersion ?? '1.0.0';
$error = $error ?? '';
$redirectTo = $redirectTo ?? '/admin/';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0b1020">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Fiesta 80s">
  <link rel="manifest" href="<?= asset_url('/manifest.webmanifest') ?>">
  <link rel="apple-touch-icon" href="<?= asset_url('/assets/logo-ciclon.jpeg') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9Oer+R4F0S3pHCFWhT6+K6nvctHf1Ra9sENBo0LRn5q+8" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= asset_url('/assets/css/pwa.css', $appVersion) ?>">
  <link rel="stylesheet" href="<?= asset_url('/assets/css/login.css', $appVersion) ?>">
  <title>Acceso administrador · Fiesta Ochentera Solidaria</title>
</head>
<body class="login-body auth-page public-visual-line">
  <main class="auth-shell container">
    <section class="auth-panel" aria-label="Acceso administrativo">
      <div class="auth-brand-strip">
        <span class="logo-badge logo-main"><img src="<?= asset_url('/assets/logo-ciclon.jpeg') ?>" alt="Ciclón Producciones"></span>
        <div><span class="kicker">Ciclón Producciones</span><h1>Panel administrativo</h1><p>Fiesta Ochentera Solidaria</p></div>
      </div>

      <div class="auth-status-row clean"><span class="connection-status" data-connection-status>En línea</span><span class="auth-mini-note">Acceso seguro</span></div>

      <div class="alert alert-danger auth-alert <?= $error ? '' : 'd-none' ?>" role="alert" aria-live="polite"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>

      <form method="post" action="<?= $basePath ?>/admin/login/" autocomplete="on" class="compact-login-form auth-form-grid">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8') ?>">
        <label><span>Usuario</span><input id="email" name="email" type="text" class="form-control" inputmode="text" autocomplete="username" placeholder="adminfiesta" required autofocus></label>
        <label><span>Contraseña</span><div class="input-group"><input id="password" name="password" type="password" class="form-control" autocomplete="current-password" placeholder="••••••••" required><button class="btn btn-outline-light btn-toggle-password" type="button" data-toggle-password>Mostrar</button></div></label>
        <div class="auth-options"><label class="form-check m-0"><input class="form-check-input" type="checkbox" name="remember" value="1"> <span class="form-check-label">Recordarme</span></label><a class="link" href="<?= $basePath ?>/admin/forgot-password/">Recuperar acceso</a></div>
        <button class="btn auth-submit w-100" type="submit" data-requires-online>Ingresar</button>
        <div class="processing small">Validando credenciales…</div>
      </form>

      <footer class="auth-footer"><a class="link" href="<?= $basePath ?>/">Volver al sitio público</a><span>v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span></footer>
    </section>
  </main>
  <script src="<?= asset_url('/assets/js/install-pwa.js', $appVersion) ?>" defer></script>
  <script src="<?= asset_url('/assets/js/service-worker-register.js', $appVersion) ?>" defer></script>
  <script src="<?= asset_url('/assets/js/app-update.js', $appVersion) ?>" defer></script>
  <script src="<?= asset_url('/assets/js/connection-status.js', $appVersion) ?>" defer></script>
  <script>
    document.querySelector('[data-toggle-password]')?.addEventListener('click', (event) => { const input = document.getElementById('password'); const show = input.type === 'password'; input.type = show ? 'text' : 'password'; event.currentTarget.textContent = show ? 'Ocultar' : 'Mostrar'; });
    document.querySelector('form')?.addEventListener('submit', (event) => event.currentTarget.classList.add('is-processing'));
  </script>
</body>
</html>
