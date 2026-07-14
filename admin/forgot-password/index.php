<?php
session_start();
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
require __DIR__ . '/../../app/Views/auth/forgot-password.php';
