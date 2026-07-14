<?php
$basePath = $basePath ?? '';
$appVersion = $appVersion ?? '1.0.0';
$userName = $userName ?? 'Administrador';
$moduleTitle = $moduleTitle ?? 'Inicio';
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
  <title>Panel de administración · Fiesta 80s</title>
</head>
<body class="login-body has-mobile-app-nav">
  <header class="container pt-3 pt-lg-4">
    <nav class="navbar navbar-expand-lg rounded-4 px-3 py-2" style="background:rgba(15,23,48,.88);border:1px solid rgba(255,255,255,.13)">
      <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-black" href="<?= $basePath ?>/admin">
        <span class="logo-badge" style="width:42px;height:42px"><img src="<?= $basePath ?>/assets/logo-ciclon.jpeg" alt="Ciclón Producciones"></span>
        <span>Fiesta 80s</span>
      </a>
      <div class="ms-auto d-flex align-items-center gap-2 small">
        <span class="connection-status" data-connection-status>En línea</span>
        <span class="text-white-50 d-none d-sm-inline"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
        <a class="btn btn-sm btn-outline-light" href="<?= $basePath ?>/admin/logout">Cerrar sesión</a>
      </div>
    </nav>
  </header>

  <main class="container py-3 py-lg-4">
    <section class="login-card card border-0 shadow-lg w-100" style="max-width:none">
      <div class="card-body p-3 p-lg-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <div>
            <span class="kicker">Panel de administración</span>
            <h1 class="h4 fw-black mt-2 mb-0"><?= htmlspecialchars($moduleTitle, ENT_QUOTES, 'UTF-8') ?></h1>
          </div>
          <button type="button" class="pwa-button" data-install-pwa>Instalar aplicación</button>
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-6 col-xl-3"><a class="btn btn-warning w-100 py-3 fw-black" href="<?= $basePath ?>/admin/reservas?status=pending">Solicitudes pendientes</a></div>
          <div class="col-12 col-md-6 col-xl-3"><a class="btn btn-outline-light w-100 py-3 fw-black" href="<?= $basePath ?>/admin/scanner">Escanear entradas</a></div>
          <div class="col-12 col-md-6 col-xl-3"><a class="btn btn-outline-light w-100 py-3 fw-black" href="<?= $basePath ?>/admin/entradas">Entradas</a></div>
          <div class="col-12 col-md-6 col-xl-3"><a class="btn btn-outline-light w-100 py-3 fw-black" href="<?= $basePath ?>/admin/configuracion">Configuración</a></div>
        </div>
      </div>
    </section>
  </main>

  <nav class="mobile-app-nav d-md-none" aria-label="Navegación móvil del panel">
    <a href="<?= $basePath ?>/admin">Inicio</a>
    <a href="<?= $basePath ?>/admin/reservas?status=pending">Solicitudes</a>
    <a href="<?= $basePath ?>/admin/scanner">Escáner</a>
    <a href="<?= $basePath ?>/admin/entradas">Entradas</a>
    <a href="<?= $basePath ?>/admin/logout">Salir</a>
  </nav>

  <script src="<?= $basePath ?>/assets/js/install-pwa.js" defer></script>
  <script src="<?= $basePath ?>/assets/js/service-worker-register.js" defer></script>
  <script src="<?= $basePath ?>/assets/js/app-update.js" defer></script>
  <script src="<?= $basePath ?>/assets/js/connection-status.js" defer></script>
</body>
</html>
