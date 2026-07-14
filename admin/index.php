<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
$basePath = app_base_path();
if (empty($_SESSION['admin_user_id'])) {
    header('Location: ' . app_url('/admin/login/'));
    exit;
}
$userName = $_SESSION['admin_user_name'] ?? 'Administrador';
require __DIR__ . '/../app/Views/admin/dashboard.php';
