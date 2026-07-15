<?php

function app_base_path(): string
{
    $configured = getenv('APP_BASE_PATH');
    if ($configured !== false && trim($configured) !== '') {
        return '/' . trim((string)$configured, '/');
    }

    $candidates = [
        $_SERVER['SCRIPT_NAME'] ?? '',
        $_SERVER['PHP_SELF'] ?? '',
    ];

    foreach ($candidates as $script) {
        $script = str_replace('\\', '/', (string)$script);
        $script = preg_replace('#/index\.php$#', '', $script) ?? $script;
        $adminPos = strpos($script, '/admin');
        if ($adminPos !== false) {
            return rtrim(substr($script, 0, $adminPos), '/');
        }
        $entradaPos = strpos($script, '/entrada');
        if ($entradaPos !== false) {
            return rtrim(substr($script, 0, $entradaPos), '/');
        }
    }

    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $requestPath = str_replace('\\', '/', $requestPath);
    foreach (['/admin', '/entrada', '/reservas'] as $segment) {
        $pos = strpos($requestPath, $segment);
        if ($pos !== false) {
            return rtrim(substr($requestPath, 0, $pos), '/');
        }
    }

    return '';
}

function app_url(string $path): string
{
    return rtrim(app_base_path(), '/') . '/' . ltrim($path, '/');
}

function asset_url(string $path, ?string $version = null): string
{
    $url = app_url($path);
    if ($version !== null && $version !== '') {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
    }
    return $url;
}

function e_attr(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
