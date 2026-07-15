<?php
$basePath = $basePath ?? '';
$appVersion = $appVersion ?? '1.0.0';
$userName = $userName ?? 'Administrador';
$module = $module ?? 'inicio';
$modules = [
    'registros' => ['title' => 'Registros onepage', 'description' => 'Administra solicitudes del formulario público, estados y entradas PDF.', 'icon' => '▤', 'badge' => 'PDF'],
    'usuarios' => ['title' => 'Usuarios y roles', 'description' => 'Gestiona cuentas administrativas, estados y permisos.', 'icon' => '◉', 'badge' => 'ADM'],
];
$sidebarGroups = [
    'main' => ['label' => null, 'items' => ['registros', 'usuarios']],
];
$module = $module === 'reservas' ? 'registros' : $module;
$module = array_key_exists($module, $modules) ? $module : 'registros';
$currentModule = $modules[$module] ?? ['title' => 'Módulo no encontrado', 'description' => 'La ruta solicitada no está disponible.', 'icon' => '!', 'group' => 'main'];
$moduleTitle = $moduleTitle ?? $currentModule['title'];
$moduleView = __DIR__ . '/modules/' . (array_key_exists($module, $modules) ? $module : 'not-found') . '.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#3b4658">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Fiesta 80s">
  <link rel="manifest" href="<?= $basePath ?>/manifest.webmanifest">
  <link rel="apple-touch-icon" href="<?= $basePath ?>/assets/logo-ciclon.jpeg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9Oer+R4F0S3pHCFWhT6+K6nvctHf1Ra9sENBo0LRn5q+8" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/pwa.css?v=<?= rawurlencode($appVersion) ?>">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/login.css?v=<?= rawurlencode($appVersion) ?>">
  <title>Administración · Registros y usuarios</title>
</head>
<body class="login-body admin-shell coreui-admin has-mobile-app-nav">
  <aside class="admin-sidebar" aria-label="Secciones del panel">
    <a class="admin-sidebar-brand" href="<?= $basePath ?>/admin/" aria-label="Administración"><span class="coreui-mark">⬡</span><strong>ADMIN</strong></a>
    <div class="admin-sidebar-menu">
      <?php foreach ($sidebarGroups as $group): ?>
        <?php if ($group['label']): ?><div class="admin-sidebar-title"><?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php foreach ($group['items'] as $key): $item = $modules[$key]; ?>
          <a class="admin-nav-link <?= $module === $key ? 'is-active' : '' ?>" href="<?= $basePath ?>/admin/<?= $key === 'inicio' ? '' : $key ?>">
            <span class="nav-icon"><?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?></span>
            <span><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($item['badge'])): ?><em><?= htmlspecialchars($item['badge'], ENT_QUOTES, 'UTF-8') ?></em><?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <div class="admin-sidebar-title">EXTRAS</div>
      <a class="admin-nav-link" href="<?= $basePath ?>/admin/logout"><span class="nav-icon">▣</span><span>Cerrar sesión</span></a>
    </div>
    <button class="admin-sidebar-collapse" type="button" aria-label="Contraer menú">‹</button>
  </aside>

  <div class="admin-main">
    <header class="admin-header">
      <nav class="admin-navbar">
        <div class="admin-top-left"><button class="admin-menu-button" type="button" aria-label="Abrir menú">☰</button><a href="<?= $basePath ?>/admin/registros">Registros onepage</a><a href="<?= $basePath ?>/admin/usuarios">Usuarios y roles</a></div>
        <div class="admin-userbar"><span class="connection-status" data-connection-status>En línea</span><span class="top-icon">♧</span><span class="top-icon">☷</span><span class="top-icon">⌑</span><span class="admin-avatar"><img src="<?= $basePath ?>/assets/logo-ciclon.jpeg" alt="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>"></span></div>
      </nav>
      <div class="admin-breadcrumb"><a href="<?= $basePath ?>/admin/">Administración</a><span>/</span><strong><?= htmlspecialchars($moduleTitle, ENT_QUOTES, 'UTF-8') ?></strong></div>
    </header>

    <main class="admin-layout">
      <section class="admin-content-card">
        <?php if (!empty($dbWarning)): ?><div class="admin-alert is-warning"><?= htmlspecialchars($dbWarning, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if (!empty($flash)): ?><div class="admin-alert is-ok"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php require $moduleView; ?>
      </section>
    </main>
  </div>

  <nav class="mobile-app-nav d-md-none" aria-label="Navegación móvil del panel">
    <a href="<?= $basePath ?>/admin/registros">Registros</a><a href="<?= $basePath ?>/admin/usuarios">Usuarios</a><a href="<?= $basePath ?>/admin/logout">Salir</a>
  </nav>

  <script src="<?= $basePath ?>/assets/js/install-pwa.js?v=<?= rawurlencode($appVersion) ?>" defer></script>
  <script src="<?= $basePath ?>/assets/js/service-worker-register.js?v=<?= rawurlencode($appVersion) ?>" defer></script>
  <script src="<?= $basePath ?>/assets/js/app-update.js?v=<?= rawurlencode($appVersion) ?>" defer></script>
  <script src="<?= $basePath ?>/assets/js/connection-status.js?v=<?= rawurlencode($appVersion) ?>" defer></script>
</body>
</html>
