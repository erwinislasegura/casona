<?php
session_start();
require_once __DIR__ . '/../../config/paths.php';

// Login desactivado temporalmente: cualquier visita al login entra directo al panel.
$_SESSION['admin_user_id'] = $_SESSION['admin_user_id'] ?? 1;
$_SESSION['admin_user_name'] = $_SESSION['admin_user_name'] ?? 'Administrador';
$_SESSION['admin_last_activity'] = time();

header('Location: ' . app_url('/admin/'));
exit;
