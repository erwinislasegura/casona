<?php
session_start();
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
$token = $_GET['token'] ?? '';
require __DIR__ . '/../../app/Views/auth/reset-password.php';
