<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../app/Models/AdminPanelRepository.php';

function pdf_escape(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text);
}


function qr_gf_mul(int $x, int $y): int
{
    $r = 0;
    while ($y > 0) {
        if ($y & 1) $r ^= $x;
        $x <<= 1;
        if ($x & 0x100) $x ^= 0x11d;
        $y >>= 1;
    }
    return $r & 0xff;
}

function qr_rs_generator(int $degree): array
{
    $poly = [1];
    $root = 1;
    for ($i = 0; $i < $degree; $i++) {
        $next = array_fill(0, count($poly) + 1, 0);
        foreach ($poly as $j => $coef) {
            $next[$j] ^= qr_gf_mul($coef, $root);
            $next[$j + 1] ^= $coef;
        }
        $poly = $next;
        $root = qr_gf_mul($root, 2);
    }
    return array_slice($poly, 0, $degree);
}

function qr_rs_remainder(array $data, int $degree): array
{
    $gen = qr_rs_generator($degree);
    $res = array_fill(0, $degree, 0);
    foreach ($data as $byte) {
        $factor = $byte ^ array_shift($res);
        $res[] = 0;
        for ($i = 0; $i < $degree; $i++) $res[$i] ^= qr_gf_mul($gen[$i], $factor);
    }
    return $res;
}

function qr_bits_append(array &$bits, int $value, int $length): void
{
    for ($i = $length - 1; $i >= 0; $i--) $bits[] = ($value >> $i) & 1;
}

function qr_matrix(string $text): array
{
    $version = 3; $size = 29; $dataCodewords = 55; $eccCodewords = 15;
    $bytes = array_values(unpack('C*', $text));
    $bits = [];
    qr_bits_append($bits, 0b0100, 4);
    qr_bits_append($bits, count($bytes), 8);
    foreach ($bytes as $byte) qr_bits_append($bits, $byte, 8);
    $capacity = $dataCodewords * 8;
    for ($i = 0; $i < min(4, $capacity - count($bits)); $i++) $bits[] = 0;
    while (count($bits) % 8 !== 0) $bits[] = 0;
    $data = [];
    for ($i = 0; $i < count($bits); $i += 8) {
        $v = 0; for ($j = 0; $j < 8; $j++) $v = ($v << 1) | $bits[$i + $j];
        $data[] = $v;
    }
    for ($pad = 0; count($data) < $dataCodewords; $pad ^= 1) $data[] = $pad ? 0x11 : 0xec;
    $codewords = array_merge($data, qr_rs_remainder($data, $eccCodewords));

    $m = array_fill(0, $size, array_fill(0, $size, false));
    $rsv = array_fill(0, $size, array_fill(0, $size, false));
    $set = function(int $x, int $y, bool $dark) use (&$m, &$rsv, $size): void { if ($x>=0&&$y>=0&&$x<$size&&$y<$size) { $m[$y][$x]=$dark; $rsv[$y][$x]=true; } };
    $finder = function(int $x, int $y) use ($set): void {
        for ($dy = -1; $dy <= 7; $dy++) for ($dx = -1; $dx <= 7; $dx++) {
            $xx=$x+$dx; $yy=$y+$dy; $dark = ($dx>=0&&$dx<=6&&$dy>=0&&$dy<=6&&($dx==0||$dx==6||$dy==0||$dy==6||($dx>=2&&$dx<=4&&$dy>=2&&$dy<=4)));
            $set($xx,$yy,$dark);
        }
    };
    $finder(0,0); $finder($size-7,0); $finder(0,$size-7);
    for ($i = 8; $i < $size - 8; $i++) { $set($i,6,$i%2===0); $set(6,$i,$i%2===0); }
    for ($dy=-2; $dy<=2; $dy++) for ($dx=-2; $dx<=2; $dx++) $set(22+$dx,22+$dy, max(abs($dx),abs($dy))!==1);
    $set(8, $size - 8, true);
    $format = '111011111000100';
    $coords1 = [[0,8],[1,8],[2,8],[3,8],[4,8],[5,8],[7,8],[8,8],[8,7],[8,5],[8,4],[8,3],[8,2],[8,1],[8,0]];
    $coords2 = [[$size-1,8],[$size-2,8],[$size-3,8],[$size-4,8],[$size-5,8],[$size-6,8],[$size-7,8],[8,$size-8],[8,$size-7],[8,$size-6],[8,$size-5],[8,$size-4],[8,$size-3],[8,$size-2],[8,$size-1]];
    foreach ($coords1 as $i=>$c) $set($c[0],$c[1],$format[$i]==='1');
    foreach ($coords2 as $i=>$c) $set($c[0],$c[1],$format[$i]==='1');

    $dataBits = [];
    foreach ($codewords as $cw) qr_bits_append($dataBits, $cw, 8);
    $bit = 0; $dir = -1;
    for ($x = $size - 1; $x >= 1; $x -= 2) {
        if ($x == 6) $x--;
        for ($y0 = 0; $y0 < $size; $y0++) {
            $y = $dir === -1 ? $size - 1 - $y0 : $y0;
            for ($xx = $x; $xx >= $x - 1; $xx--) if (!$rsv[$y][$xx]) {
                $dark = ($dataBits[$bit++] ?? 0) === 1;
                if ((($xx + $y) % 2) === 0) $dark = !$dark;
                $m[$y][$xx] = $dark;
            }
        }
        $dir *= -1;
    }
    return $m;
}

function build_tickets_pdf(array $reservation): string
{
    $lines = [
        'FIESTA OCHENTERA SOLIDARIA',
        'Entrada digital / Reserva ' . $reservation['request_code'],
        'Cliente: ' . $reservation['full_name'],
        'RUT: ' . $reservation['rut'],
        'Email: ' . $reservation['email'],
        'Personas: ' . $reservation['people_count'] . ' | Total: $' . number_format((int)$reservation['total_amount'], 0, ',', '.'),
        'Estado: ' . $reservation['status'],
        ' ',
        'Tickets emitidos:',
    ];
    foreach (($reservation['tickets'] ?? []) as $ticket) {
        $lines[] = '- ' . ($ticket['ticket_code'] ?: ('Ticket #' . $ticket['id'])) . ' | ' . $ticket['status'] . ' | ' . ($ticket['holder_name'] ?: $reservation['full_name']);
    }
    if (empty($reservation['tickets'])) $lines[] = 'Sin tickets emitidos todavía.';

    $content = "BT\n/F1 18 Tf\n50 790 Td\n";
    foreach ($lines as $index => $line) {
        if ($index === 1) $content .= "/F1 13 Tf\n";
        if ($index > 0) $content .= "0 -24 Td\n";
        $content .= '(' . pdf_escape((string)$line) . ") Tj\n";
    }
    $content .= "ET\n";
    $qrY = 650;
    foreach (array_slice(($reservation['tickets'] ?? []), 0, 4) as $ticket) {
        if (empty($ticket['qr_token'])) continue;
        $matrix = qr_matrix((string)$ticket['qr_token']);
        $cell = 3.2; $left = 400; $top = $qrY;
        $content .= "0 0 0 rg\n";
        foreach ($matrix as $row => $cols) foreach ($cols as $col => $dark) if ($dark) {
            $x = $left + ($col * $cell); $y = $top - ($row * $cell);
            $content .= sprintf('%.2F %.2F %.2F %.2F re f\n', $x, $y, $cell, $cell);
        }
        $content .= "BT\n/F1 8 Tf\n400 " . ($qrY - 102) . " Td\n(" . pdf_escape((string)($ticket['ticket_code'] ?? 'Ticket')) . ") Tj\nET\n";
        $qrY -= 145;
    }

    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    return $pdf;
}


$basePath = app_base_path();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/admin/', PHP_URL_PATH) ?: '/admin/';
$adminPos = strpos($path, '/admin');
$module = 'inicio';
if ($adminPos !== false) {
    $modulePath = trim(substr($path, $adminPos + strlen('/admin')), '/');
    $module = $modulePath === '' ? 'inicio' : (strtok($modulePath, '/') ?: 'inicio');
}

if ($module === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . app_url('/admin/login/'));
    exit;
}

if (empty($_SESSION['admin_user_id'])) {
    header('Location: ' . app_url('/admin/login/'));
    exit;
}

$userName = $_SESSION['admin_user_name'] ?? 'Administrador';
$adminUserId = (int)$_SESSION['admin_user_id'];
$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);
$panelData = AdminPanelRepository::fallbackData();
$scannerResult = null;

try {
    $factory = require __DIR__ . '/../config/database.php';
    $panelRepository = new AdminPanelRepository($factory());

    if ($module === 'entrada-pdf') {
        $reservation = $panelRepository->reservaWithTickets((int)($_GET['id'] ?? 0));
        if (!$reservation) {
            http_response_code(404);
            echo 'Reserva no encontrada.';
            exit;
        }
        $pdf = build_tickets_pdf($reservation);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="entrada-' . $reservation['request_code'] . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    if ($module === 'comprobante') {
        $receipt = $panelRepository->reservaReceipt((int)($_GET['id'] ?? 0));
        if (!$receipt) {
            http_response_code(404);
            echo 'Comprobante no encontrado.';
            exit;
        }
        header('Content-Type: ' . $receipt['mime']);
        header('Content-Disposition: inline; filename="' . addslashes($receipt['name']) . '"');
        echo $receipt['content'];
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'update_reserva') {
            $panelRepository->updateReservaStatus((int)($_POST['reserva_id'] ?? 0), (string)($_POST['status'] ?? 'pending'));
            $_SESSION['admin_flash'] = 'Reserva actualizada correctamente.';
            header('Location: ' . app_url('/admin/reservas'));
            exit;
        }
        if ($action === 'edit_reserva') {
            $panelRepository->updateReservaDetails((int)($_POST['reserva_id'] ?? 0), $_POST);
            $_SESSION['admin_flash'] = 'Datos de la reserva actualizados correctamente.';
            header('Location: ' . app_url('/admin/reservas'));
            exit;
        }
        if ($action === 'delete_reserva') {
            $panelRepository->deleteReserva((int)($_POST['reserva_id'] ?? 0));
            $_SESSION['admin_flash'] = 'Reserva eliminada correctamente.';
            header('Location: ' . app_url('/admin/reservas'));
            exit;
        }
        if ($action === 'save_settings') {
            $panelRepository->saveSettings($_POST);
            $_SESSION['admin_flash'] = 'Configuración guardada correctamente.';
            header('Location: ' . app_url('/admin/configuracion'));
            exit;
        }
        if ($action === 'validate_ticket') {
            $scannerResult = $panelRepository->validateTicket((string)($_POST['qr_token'] ?? ''), $adminUserId);
        }
    }

    $panelData = $panelRepository->dashboardData();
} catch (Throwable $exception) {
    error_log('[admin-panel] ' . $exception::class . ': ' . $exception->getMessage());
    // La vista muestra una advertencia y datos vacíos para no romper el panel si falta la BD.
}

extract($panelData, EXTR_SKIP);
require __DIR__ . '/../app/Views/admin/dashboard.php';
