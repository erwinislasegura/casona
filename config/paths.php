<?php

function app_base_path(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos = strpos($script, '/admin/');
    if ($pos === false) {
        $pos = strrpos($script, '/admin');
    }
    if ($pos === false) {
        return '';
    }
    return rtrim(substr($script, 0, $pos), '/');
}

function app_url(string $path): string
{
    return app_base_path() . '/' . ltrim($path, '/');
}
