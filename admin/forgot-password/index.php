<?php
session_start();
require_once __DIR__ . '/../../config/paths.php';
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
$basePath = app_base_path();
require __DIR__ . '/../../app/Views/auth/forgot-password.php';
