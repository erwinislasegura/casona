<?php
$basePath = $basePath ?? '';
$appVersion = $appVersion ?? '1.0.0';
$userName = $userName ?? 'Administrador';
$module = $module ?? 'inicio';
$modules = [
    'inicio' => ['title' => 'Inicio', 'description' => 'Resumen general del panel y accesos rápidos.'],
    'reservas' => ['title' => 'Solicitudes pendientes', 'description' => 'Gestiona las reservas y solicitudes de mesas.'],
    'scanner' => ['title' => 'Escáner de entradas', 'description' => 'Valida entradas QR desde el dispositivo autorizado.'],
    'entradas' => ['title' => 'Entradas', 'description' => 'Revisa y administra las entradas emitidas.'],
    'configuracion' => ['title' => 'Configuración', 'description' => 'Ajustes generales del evento y del panel.'],
];
$currentModule = $modules[$module] ?? ['title' => 'Módulo no encontrado', 'description' => 'La ruta solicitada no está disponible.'];
$moduleTitle = $moduleTitle ?? $currentModule['title'];
$moduleView = __DIR__ . '/modules/' . (array_key_exists($module, $modules) ? $module : 'not-found') . '.php';
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
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/pwa.css?v=<?= rawurlencode($appVersion) ?>">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/login.css?v=<?= rawurlencode($appVersion) ?>">
  <title>Panel de administración · Fiesta 80s</title>
</head>
<body class="login-body admin-shell has-mobile-app-nav">
  <header class="admin-header">
    <nav class="admin-navbar container">
      <a class="admin-brand" href="<?= $basePath ?>/admin/">
        <span class="logo-badge"><img src="<?= $basePath ?>/assets/logo-ciclon.jpeg" alt="Ciclón Producciones"></span>
        <span><strong>Fiesta 80s</strong><small>Panel de administración</small></span>
      </a>
      <div class="admin-userbar">
        <span class="connection-status" data-connection-status>En línea</span>
        <span class="admin-user-name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
        <a class="btn btn-sm btn-outline-light" href="<?= $basePath ?>/admin/logout">Salir</a>
      </div>
    </nav>
  </header>

  <main class="container admin-layout">
    <aside class="admin-sidebar" aria-label="Secciones del panel">
      <?php foreach ($modules as $key => $item): ?>
        <a class="admin-nav-link <?= $module === $key ? 'is-active' : '' ?>" href="<?= $basePath ?>/admin/<?= $key === 'inicio' ? '' : $key ?>">
          <span><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
      <?php endforeach; ?>
    </aside>

    <section class="admin-content-card">
      <div class="admin-hero-row">
        <div>
          <span class="kicker">Panel operativo</span>
          <h1><?= htmlspecialchars($moduleTitle, ENT_QUOTES, 'UTF-8') ?></h1>
          <p><?= htmlspecialchars($currentModule['description'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <button type="button" class="pwa-button" data-install-pwa>Instalar aplicación</button>
      </div>

      <div class="admin-actions-grid">
        <a class="admin-action is-warning" href="<?= $basePath ?>/admin/reservas?status=pending"><strong>Solicitudes pendientes</strong><span>Ver reservas por confirmar</span></a>
        <a class="admin-action" href="<?= $basePath ?>/admin/scanner"><strong>Escanear entradas</strong><span>Validación QR</span></a>
        <a class="admin-action" href="<?= $basePath ?>/admin/entradas"><strong>Entradas</strong><span>Listado y estado</span></a>
        <a class="admin-action" href="<?= $basePath ?>/admin/configuracion"><strong>Configuración</strong><span>Ajustes del evento</span></a>
      </div>

      <?php require $moduleView; ?>
    </section>
  </main>

  <nav class="mobile-app-nav d-md-none" aria-label="Navegación móvil del panel">
    <a href="<?= $basePath ?>/admin/">Inicio</a>
    <a href="<?= $basePath ?>/admin/reservas?status=pending">Solicitudes</a>
    <a href="<?= $basePath ?>/admin/scanner">Escáner</a>
    <a href="<?= $basePath ?>/admin/entradas">Entradas</a>
    <a href="<?= $basePath ?>/admin/logout">Salir</a>
  </nav>

  <script src="<?= $basePath ?>/assets/js/install-pwa.js?v=<?= rawurlencode($appVersion) ?>" defer></script>
  <script src="<?= $basePath ?>/assets/js/service-worker-register.js?v=<?= rawurlencode($appVersion) ?>" defer></script>
  <script src="<?= $basePath ?>/assets/js/app-update.js?v=<?= rawurlencode($appVersion) ?>" defer></script>
  <script src="<?= $basePath ?>/assets/js/connection-status.js?v=<?= rawurlencode($appVersion) ?>" defer></script>
</body>
</html>
