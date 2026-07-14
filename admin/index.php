<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
$basePath = app_base_path();

// Acceso administrativo temporal sin autenticación.
$_SESSION['admin_user_id'] = $_SESSION['admin_user_id'] ?? 1;
$_SESSION['admin_user_name'] = $_SESSION['admin_user_name'] ?? 'Administrador';
$_SESSION['admin_last_activity'] = time();

$userName = $_SESSION['admin_user_name'];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/admin/', PHP_URL_PATH) ?: '/admin/';
$adminPos = strpos($path, '/admin');
$module = 'inicio';
if ($adminPos !== false) {
    $modulePath = trim(substr($path, $adminPos + strlen('/admin')), '/');
    $module = $modulePath === '' ? 'inicio' : (strtok($modulePath, '/') ?: 'inicio');
}

if ($module === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . app_url('/admin/login/'));
    exit;
}

$allowedModules = ['inicio', 'reservas', 'scanner', 'entradas', 'configuracion'];
if (!in_array($module, $allowedModules, true)) {
    $module = 'inicio';
}

require __DIR__ . '/../app/Views/admin/dashboard.php';
