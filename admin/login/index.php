<?php
session_start();
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../app/Models/AdminAuthRepository.php';
require_once __DIR__ . '/../../app/Controllers/AdminAuthController.php';

$basePath = app_base_path();
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
$error = '';
$redirectTo = '/admin/';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrfToken, $postedToken)) {
        $error = 'La sesión expiró. Intenta nuevamente.';
    } else {
        try {
            $factory = require __DIR__ . '/../../config/database.php';
            $controller = new AdminAuthController(new AdminAuthRepository($factory()));
            $target = $controller->login($_POST, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? '');
            header('Location: ' . (str_starts_with($target, $basePath . '/') ? $target : app_url($target)));
            exit;
        } catch (Throwable $exception) {
            error_log('[admin-login] ' . $exception::class . ': ' . $exception->getMessage());
            $error = 'No fue posible conectar con la base de datos. Revisa la configuración del servidor. Detalle local: ' . $exception->getMessage();
        }
    }
}

if (($_GET['error'] ?? '') === 'invalid') {
    $error = 'Usuario o contraseña inválidos.';
}

require __DIR__ . '/../../app/Views/auth/login.php';
