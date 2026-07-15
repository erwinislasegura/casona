<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../app/Models/AdminPanelRepository.php';
require_once __DIR__ . '/../app/Support/TicketSupport.php';

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    http_response_code(404);
    echo 'Entrada no encontrada.';
    exit;
}

try {
    $factory = require __DIR__ . '/../config/database.php';
    $repository = new AdminPanelRepository($factory());
    $data = $repository->publicTicketByToken($token);
    if (!$data) {
        http_response_code(404);
        echo 'Entrada no encontrada o no disponible.';
        exit;
    }
    $ticketUrl = ticket_public_url($token);
    echo ticket_html($data['reservation'], $data['ticket'], $ticketUrl, false);
} catch (Throwable $exception) {
    error_log('[public-ticket] ' . $exception::class . ': ' . $exception->getMessage());
    http_response_code(500);
    echo 'No fue posible cargar la entrada.';
}
