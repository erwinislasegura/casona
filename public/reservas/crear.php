<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/ReservationRepository.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.']);
    exit;
}

try {
    $factory = require __DIR__ . '/../../config/database.php';
    $repository = new ReservationRepository($factory());
    $reservation = $repository->create($_POST, $_FILES['receipt'] ?? null);
    echo json_encode([
        'ok' => true,
        'code' => $reservation['request_code'],
        'message' => 'Solicitud registrada correctamente. El equipo revisará el comprobante antes de emitir la entrada.',
    ]);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    error_log('[reservation-create] ' . $exception::class . ': ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'No fue posible registrar la solicitud. Intenta nuevamente.']);
}
