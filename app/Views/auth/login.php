<?php
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
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/assets/logo-ciclon.jpeg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9Oer+R4F0S3pHCFWhT6+K6nvctHf1Ra9sENBo0LRn5q+8" crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/css/pwa.css">
  <link rel="stylesheet" href="/assets/css/login.css">
  <title>Panel de administración · Fiesta Ochentera Solidaria</title>
</head>
<body class="login-body">
  <main class="container py-3 py-lg-4">
    <div class="login-shell row g-0 mx-auto overflow-hidden">
      <section class="login-visual col-lg-6 d-none d-lg-flex" aria-label="Identidad visual Fiesta Ochentera">
        <div class="logo-row mb-auto">
          <span class="logo-badge"><img src="/assets/logo-san-gabriel.png" alt="San Gabriel"></span>
          <span class="logo-badge"><img src="/assets/logo-ciclon.jpeg" alt="Ciclón Producciones"></span>
          <span class="logo-badge"><img src="/assets/logo-la-casona.jpeg" alt="Club La Casona"></span>
        </div>
        <div class="visual-copy mt-auto">
          <span class="kicker">Acceso restringido</span>
          <h1>Fiesta Ochentera Solidaria</h1>
          <p class="mb-0">Administración compacta de reservas, entradas virtuales y control de acceso.</p>
        </div>
      </section>

      <section class="login-panel col-12 col-lg-6">
        <div class="login-card card border-0 shadow-lg">
          <div class="card-body p-3 p-sm-4">
            <div class="logo-row mobile-logos justify-content-center mb-3 d-lg-none">
              <span class="logo-badge"><img src="/assets/logo-san-gabriel.png" alt="San Gabriel"></span>
              <span class="logo-badge"><img src="/assets/logo-ciclon.jpeg" alt="Ciclón Producciones"></span>
              <span class="logo-badge"><img src="/assets/logo-la-casona.jpeg" alt="Club La Casona"></span>
            </div>

            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
              <div>
                <span class="kicker">Panel seguro</span>
                <h2 class="h4 fw-black mt-2 mb-1">Panel de administración</h2>
                <p class="subtitle mb-1">Fiesta Ochentera Solidaria</p>
              </div>
              <span class="connection-status flex-shrink-0" data-connection-status>En línea</span>
            </div>
            <p class="restricted small mb-3">Acceso exclusivo para personal autorizado.</p>

            <div class="alert alert-danger py-2 small <?= $error ? '' : 'invisible' ?>" role="alert" aria-live="polite">
              <?= $error ? htmlspecialchars($error, ENT_QUOTES, 'UTF-8') : 'Mensaje del sistema' ?>
            </div>

            <form method="post" action="/admin/login" autocomplete="on" class="compact-login-form">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectTo ?? '/admin', ENT_QUOTES, 'UTF-8') ?>">

              <div class="mb-2">
                <label for="email" class="form-label small fw-bold mb-1">Correo electrónico</label>
                <input id="email" name="email" type="email" class="form-control form-control-lg" inputmode="email" autocomplete="username" required>
              </div>

              <div class="mb-2">
                <label for="password" class="form-label small fw-bold mb-1">Contraseña</label>
                <div class="input-group input-group-lg">
                  <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                  <button class="btn btn-outline-light" type="button" data-toggle-password>Mostrar</button>
                </div>
              </div>

              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 my-3 small">
                <label class="form-check m-0"><input class="form-check-input" type="checkbox" name="remember" value="1"> <span class="form-check-label">Recordarme</span></label>
                <a class="link" href="/admin/forgot-password">¿Olvidaste tu contraseña?</a>
              </div>

              <button class="btn btn-warning w-100 fw-black py-2" type="submit" data-requires-online>Ingresar al panel</button>
              <div class="processing small mt-2">Validando credenciales…</div>
            </form>

            <div class="secondary-actions mt-3 text-center small">
              <a class="link" href="/">Volver al sitio público</a>
            </div>

            <div class="login-pwa mt-3">
              <button type="button" class="pwa-button" data-install-pwa>Instalar aplicación</button>
              <div class="ios-install-hint" data-ios-install-hint>Para instalar, abre el menú Compartir y selecciona Agregar a pantalla de inicio.</div>
            </div>

            <footer class="login-footer mt-3 small">Versión <?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?> · Acceso protegido con CSRF, rate limiting, bloqueo temporal, sesiones regeneradas y cookies HttpOnly/SameSite.</footer>
          </div>
        </div>
      </section>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" defer></script>
  <script src="/assets/js/install-pwa.js" defer></script>
  <script src="/assets/js/service-worker-register.js" defer></script>
  <script src="/assets/js/app-update.js" defer></script>
  <script src="/assets/js/connection-status.js" defer></script>
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
