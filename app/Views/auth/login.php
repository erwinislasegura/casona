<?php
$basePath = $basePath ?? '';
$appVersion = $appVersion ?? '1.0.0';
$error = $error ?? '';
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
  <link rel="manifest" href="<?= $basePath ?>/manifest.webmanifest">
  <link rel="apple-touch-icon" href="<?= $basePath ?>/assets/logo-ciclon.jpeg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9Oer+R4F0S3pHCFWhT6+K6nvctHf1Ra9sENBo0LRn5q+8" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/pwa.css">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/login.css">
  <title>Panel de administración · Fiesta Ochentera Solidaria</title>
</head>
<body class="login-body auth-page">
  <main class="auth-wrap container">
    <section class="auth-card row g-0 mx-auto" aria-label="Acceso administrativo">
      <div class="auth-visual col-lg-5">
        <div class="auth-logos" aria-label="Organizadores">
          <span class="logo-badge"><img src="<?= $basePath ?>/assets/logo-san-gabriel.png" alt="San Gabriel"></span>
          <span class="logo-badge logo-main"><img src="<?= $basePath ?>/assets/logo-ciclon.jpeg" alt="Ciclón Producciones"></span>
          <span class="logo-badge"><img src="<?= $basePath ?>/assets/logo-la-casona.jpeg" alt="Club La Casona"></span>
        </div>
        <div class="auth-visual-copy">
          <span class="kicker">Fiesta Ochentera Solidaria</span>
          <h1>Panel operativo</h1>
          <p>Reservas, entradas virtuales y control de acceso con una interfaz compacta para el equipo.</p>
        </div>
      </div>

      <div class="auth-form col-lg-7">
        <div class="auth-form-inner">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <div>
              <span class="kicker">Acceso restringido</span>
              <h2 class="auth-title">Panel de administración</h2>
              <p class="auth-subtitle">Fiesta Ochentera Solidaria</p>
            </div>
            <span class="connection-status flex-shrink-0" data-connection-status>En línea</span>
          </div>

          <p class="auth-note">Ingresa con tus credenciales asignadas. El acceso queda auditado por seguridad.</p>

          <div class="alert alert-danger auth-alert <?= $error ? '' : 'invisible' ?>" role="alert" aria-live="polite">
            <?= $error ? htmlspecialchars($error, ENT_QUOTES, 'UTF-8') : 'Mensaje del sistema' ?>
          </div>

          <form method="post" action="<?= $basePath ?>/admin/login" autocomplete="on" class="compact-login-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectTo ?? ($basePath . '/admin'), ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-2">
              <label for="email" class="form-label">Correo electrónico</label>
              <input id="email" name="email" type="email" class="form-control" inputmode="email" autocomplete="username" required>
            </div>

            <div class="mb-2">
              <label for="password" class="form-label">Contraseña</label>
              <div class="input-group">
                <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                <button class="btn btn-outline-light btn-toggle-password" type="button" data-toggle-password>Mostrar</button>
              </div>
            </div>

            <div class="auth-options">
              <label class="form-check m-0"><input class="form-check-input" type="checkbox" name="remember" value="1"> <span class="form-check-label">Recordarme</span></label>
              <a class="link" href="<?= $basePath ?>/admin/forgot-password">¿Olvidaste tu contraseña?</a>
            </div>

            <button class="btn auth-submit w-100" type="submit" data-requires-online>Ingresar al panel</button>
            <div class="processing small mt-2">Validando credenciales…</div>
          </form>

          <div class="auth-links">
            <a class="link" href="<?= $basePath ?>/">Volver al sitio público</a>
            <button type="button" class="pwa-button" data-install-pwa>Instalar aplicación</button>
          </div>
          <div class="ios-install-hint" data-ios-install-hint>Para instalar, abre el menú Compartir y selecciona Agregar a pantalla de inicio.</div>

          <footer class="login-footer">Versión <?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?> · Acceso protegido con CSRF, bloqueo temporal y sesiones seguras.</footer>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" defer></script>
  <script src="<?= $basePath ?>/assets/js/install-pwa.js" defer></script>
  <script src="<?= $basePath ?>/assets/js/service-worker-register.js" defer></script>
  <script src="<?= $basePath ?>/assets/js/app-update.js" defer></script>
  <script src="<?= $basePath ?>/assets/js/connection-status.js" defer></script>
  <script>
    document.querySelector('[data-toggle-password]')?.addEventListener('click', (event) => {
      const input = document.getElementById('password');
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      event.currentTarget.textContent = show ? 'Ocultar' : 'Mostrar';
    });
    document.querySelector('form')?.addEventListener('submit', (event) => event.currentTarget.classList.add('is-processing'));
  </script>
</body>
</html>
