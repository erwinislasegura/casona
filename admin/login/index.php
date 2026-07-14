<?php
session_start();
require_once __DIR__ . '/../../config/paths.php';

$basePath = app_base_path();
$redirectTo = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? app_url('/admin/');
$error = '';
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'La sesión expiró. Vuelve a intentarlo.';
    } else {
        require_once __DIR__ . '/../../app/Models/AdminAuthRepository.php';
        require_once __DIR__ . '/../../app/Controllers/AdminAuthController.php';
        $databaseFactory = require __DIR__ . '/../../config/database.php';
        try {
            $controller = new AdminAuthController(new AdminAuthRepository($databaseFactory()));
            $target = $controller->login($_POST, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? '');
            header('Location: ' . (str_starts_with($target, $basePath . '/') ? $target : $basePath . $target));
            exit;
        } catch (Throwable) {
            $error = 'No fue posible conectar con el panel. Intenta nuevamente.';
        }
    }
} elseif (isset($_GET['error'])) {
    $error = 'No fue posible iniciar sesión. Revisa tus credenciales o intenta más tarde.';
}

require __DIR__ . '/../../app/Views/auth/login.php';
