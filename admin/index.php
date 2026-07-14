<?php
session_start();
if (empty($_SESSION['admin_user_id'])) {
    header('Location: /admin/login');
    exit;
}
$userName = $_SESSION['admin_user_name'] ?? 'Administrador';
require __DIR__ . '/../app/Views/admin/dashboard.php';
