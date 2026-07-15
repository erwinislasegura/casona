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

function pdf_text(float $x, float $y, int $size, string $text, string $color = '1 1 1', string $font = 'F1'): string
{
    return $color . " rg\nBT\n/" . $font . ' ' . $size . " Tf\n" . $x . ' ' . $y . " Td\n(" . pdf_escape($text) . ") Tj\nET\n";
}

function pdf_rect(float $x, float $y, float $w, float $h, string $color): string
{
    return $color . " rg\n" . sprintf('%.2F %.2F %.2F %.2F re f', $x, $y, $w, $h) . "\n";
}


function pdf_load_image(string $path): ?array
{
    if (!is_file($path)) return null;
    $info = getimagesize($path);
    if (!$info) return null;
    $mime = $info['mime'] ?? '';
    if ($mime === 'image/jpeg') {
        return ['data' => file_get_contents($path), 'width' => (int)$info[0], 'height' => (int)$info[1]];
    }
    if (!function_exists('imagejpeg')) return null;
    $image = match ($mime) {
        'image/png' => imagecreatefrompng($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
        default => false,
    };
    if (!$image) return null;
    ob_start();
    imagejpeg($image, null, 88);
    $data = ob_get_clean();
    imagedestroy($image);
    return ['data' => $data, 'width' => (int)$info[0], 'height' => (int)$info[1]];
}

function pdf_draw_image(string $name, float $x, float $y, float $w, float $h): string
{
    return "q\n" . $w . " 0 0 " . $h . " " . $x . " " . $y . " cm\n/" . $name . " Do\nQ\n";
}

function pdf_qr(float $x, float $y, float $cell, string $token): string
{
    $matrix = qr_matrix($token);
    $out = pdf_rect($x - 10, $y - (count($matrix) * $cell) - 10, (count($matrix) * $cell) + 20, (count($matrix) * $cell) + 20, '1 1 1');
    $out .= "0 0 0 rg\n";
    foreach ($matrix as $row => $cols) foreach ($cols as $col => $dark) if ($dark) {
        $out .= sprintf('%.2F %.2F %.2F %.2F re f\n', $x + ($col * $cell), $y - ($row * $cell), $cell, $cell);
    }
    return $out;
}

function build_ticket_page(array $reservation, array $ticket, int $number, int $total, array $imageNames): string
{
    $ticketCode = (string)($ticket['ticket_code'] ?: ('Ticket #' . $ticket['id']));
    $holder = mb_substr((string)($ticket['holder_name'] ?: $reservation['full_name']), 0, 28);
    $token = (string)($ticket['qr_token'] ?? '');
    $people = max(1, (int)$reservation['people_count']);
    $folio = mb_substr($ticketCode, 0, 32);
    $content = '';

    // Compact event-pass format: 680 x 260 pt, optimized for mobile download/print.
    $content .= pdf_rect(0, 0, 680, 260, '1 0.47 0.05');
    $content .= pdf_rect(42, 38, 596, 184, '0.015 0.025 0.055');
    $content .= pdf_rect(46, 42, 588, 176, '0.025 0.038 0.075');

    // Hero strip inspired by a horizontal event pass, with the event artwork used as a decorative accent.
    $content .= pdf_rect(46, 42, 426, 176, '0.015 0.025 0.055');
    $content .= pdf_rect(48, 44, 422, 172, '0.025 0.040 0.085');
    if (isset($imageNames['Hero'])) $content .= pdf_draw_image($imageNames['Hero'], 318, 42, 146, 92);

    // Right validation panel.
    $content .= "1 1 1 RG\n1.2 w\n[10 10] 0 d\n484 52 m\n484 208 l\nS\n[] 0 d\n";
    $content .= pdf_rect(500, 58, 118, 148, '0.96 0.98 1');
    if ($token !== '') $content .= pdf_qr(512, 190, 3.25, $token);
    $content .= pdf_text(516, 70, 7, 'QR VALIDACION', '0.08 0.10 0.16', 'F2');

    // Header and labels.
    $content .= pdf_text(320, 226, 12, 'TICKET', '1 1 1', 'F2');
    $content .= pdf_text(333, 212, 8, 'EVENTO', '1 1 1');
    $content .= pdf_text(68, 184, 30, 'PARTY PASS', '1 1 1', 'F2');
    $content .= pdf_text(70, 166, 8, 'FIESTA OCHENTERA SOLIDARIA', '0.22 0.90 1', 'F2');

    // Detail pills.
    $content .= pdf_rect(68, 126, 128, 28, '0.05 0.90 0.95');
    $content .= pdf_rect(196, 126, 214, 28, '0.025 0.035 0.075');
    $content .= "0.80 0.86 0.95 RG\n0.8 w\n196 126 214 28 re S\n";
    $content .= pdf_text(82, 136, 10, '24 JULIO 2026', '0.02 0.05 0.09', 'F2');
    $content .= pdf_text(214, 136, 9, 'Club La Casona · Los Angeles', '1 1 1');

    $content .= pdf_text(68, 102, 8, 'TITULAR', '0.80 0.86 0.95');
    $content .= pdf_text(132, 96, 16, strtoupper($holder), '1 0.47 0.05', 'F2');
    $content .= pdf_text(68, 78, 8, 'FOLIO', '0.80 0.86 0.95');
    $content .= pdf_text(132, 78, 8, $folio, '1 1 1');
    $content .= pdf_text(68, 60, 8, 'PERSONAS', '0.80 0.86 0.95');
    $content .= pdf_text(132, 54, 16, (string)$people, '0.05 0.90 0.95', 'F2');
    $content .= pdf_text(176, 60, 8, 'Presentar este QR en acceso para validar la entrada.', '0.80 0.86 0.95');

    // Decorative dot matrix.
    for ($x = 424; $x <= 456; $x += 12) {
        for ($y = 170; $y <= 206; $y += 12) {
            $content .= sprintf('0.95 0.97 1 rg\n%.2F %.2F 2.20 2.20 re f\n', $x, $y);
        }
    }

    return $content;
}

function build_tickets_pdf(array $reservation): string
{
    $tickets = $reservation['tickets'] ?? [];
    if (empty($tickets)) $tickets = [['id' => 0, 'ticket_code' => $reservation['request_code'] . '-GRUPO', 'holder_name' => $reservation['full_name'], 'qr_token' => '', 'status' => 'pending']];
    $imagePaths = [
        'Hero' => dirname(__DIR__) . '/assets/abba-cta.jpg',
        'LogoSan' => dirname(__DIR__) . '/assets/logo-san-gabriel.png',
        'LogoCiclon' => dirname(__DIR__) . '/assets/logo-ciclon.jpeg',
        'LogoCasona' => dirname(__DIR__) . '/assets/logo-la-casona.jpeg',
    ];
    $objects = [];
    $objects[1] = '';
    $objects[2] = '';
    $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
    $imageNames = [];
    $nextId = 5;
    foreach ($imagePaths as $name => $path) {
        $image = pdf_load_image($path);
        if (!$image || empty($image['data'])) continue;
        $objects[$nextId] = '<< /Type /XObject /Subtype /Image /Width ' . $image['width'] . ' /Height ' . $image['height'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($image['data']) . " >>\nstream\n" . $image['data'] . "\nendstream";
        $imageNames[$name] = 'Im' . $nextId;
        $nextId++;
    }
    $kids = [];
    foreach (array_values($tickets) as $index => $ticket) {
        $pageId = $nextId++;
        $contentId = $nextId++;
        $kids[] = $pageId . ' 0 R';
        $stream = build_ticket_page($reservation, $ticket, $index + 1, count($tickets), $imageNames);
        $xObjectItems = [];
        foreach ($imageNames as $imageName) { $id = (int)substr($imageName, 2); $xObjectItems[] = '/' . $imageName . ' ' . $id . ' 0 R'; }
        $xObject = $xObjectItems ? ' /XObject << ' . implode(' ', $xObjectItems) . ' >>' : '';
        $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 680 260] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>' . $xObject . ' >> /Contents ' . $contentId . ' 0 R >>';
        $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
    }
    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $max = max(array_keys($objects));
    $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= $max; $i++) $pdf .= str_pad((string)($offsets[$i] ?? 0), 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    $pdf .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    return $pdf;
}


$basePath = app_base_path();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/admin/', PHP_URL_PATH) ?: '/admin/';
$adminPos = strpos($path, '/admin');
$module = 'registros';
if ($adminPos !== false) {
    $modulePath = trim(substr($path, $adminPos + strlen('/admin')), '/');
    $module = $modulePath === '' ? 'registros' : (strtok($modulePath, '/') ?: 'registros');
    if ($module === 'reservas') $module = 'registros';
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
            $_SESSION['admin_flash'] = ((string)($_POST['status'] ?? '') === 'approved') ? 'Registro aprobado y entrada PDF generada correctamente.' : 'Registro actualizado correctamente.';
            header('Location: ' . app_url('/admin/registros'));
            exit;
        }
        if ($action === 'edit_reserva') {
            $panelRepository->updateReservaDetails((int)($_POST['reserva_id'] ?? 0), $_POST);
            $_SESSION['admin_flash'] = 'Datos de la reserva actualizados correctamente.';
            header('Location: ' . app_url('/admin/registros'));
            exit;
        }
        if ($action === 'delete_reserva') {
            $panelRepository->deleteReserva((int)($_POST['reserva_id'] ?? 0));
            $_SESSION['admin_flash'] = 'Reserva eliminada correctamente.';
            header('Location: ' . app_url('/admin/registros'));
            exit;
        }
        if ($action === 'save_settings') {
            $panelRepository->saveSettings($_POST);
            $_SESSION['admin_flash'] = 'Configuración guardada correctamente.';
            header('Location: ' . app_url('/admin/configuracion'));
            exit;
        }
        if ($action === 'create_admin_user') {
            $panelRepository->saveAdminUser($_POST);
            $_SESSION['admin_flash'] = 'Usuario creado correctamente.';
            header('Location: ' . app_url('/admin/usuarios'));
            exit;
        }
        if ($action === 'update_admin_user') {
            $panelRepository->saveAdminUser($_POST, (int)($_POST['user_id'] ?? 0));
            $_SESSION['admin_flash'] = 'Usuario actualizado correctamente.';
            header('Location: ' . app_url('/admin/usuarios'));
            exit;
        }
        if ($action === 'validate_ticket') {
            $scannerResult = $panelRepository->validateTicket((string)($_POST['qr_token'] ?? ''), $adminUserId);
        }
    }

    $panelData = $panelRepository->dashboardData();
    if ($module === 'registros' && isset($_GET['status'])) {
        $panelData['reservas'] = $panelRepository->reservas((string)$_GET['status']);
    }
} catch (Throwable $exception) {
    error_log('[admin-panel] ' . $exception::class . ': ' . $exception->getMessage());
    // La vista muestra una advertencia y datos vacíos para no romper el panel si falta la BD.
}

extract($panelData, EXTR_SKIP);
require __DIR__ . '/../app/Views/admin/dashboard.php';
