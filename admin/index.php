<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
$basePath = app_base_path();

// Acceso administrativo temporal sin autenticación.
$_SESSION['admin_user_id'] = $_SESSION['admin_user_id'] ?? 1;
$_SESSION['admin_user_name'] = $_SESSION['admin_user_name'] ?? 'Administrador';
$_SESSION['admin_last_activity'] = time();

$userName = $_SESSION['admin_user_name'];
require __DIR__ . '/../app/Views/admin/dashboard.php';
