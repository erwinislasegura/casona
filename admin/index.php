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

function pdf_text(float $x, float $y, int $size, string $text, string $color = '1 1 1'): string
{
    return $color . " rg\nBT\n/F1 " . $size . " Tf\n" . $x . ' ' . $y . " Td\n(" . pdf_escape($text) . ") Tj\nET\n";
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
    $holder = (string)($ticket['holder_name'] ?: $reservation['full_name']);
    $token = (string)($ticket['qr_token'] ?? '');
    $people = (int)$reservation['people_count'];
    $content = '';
    $content .= pdf_rect(0, 0, 595, 842, '0.025 0.035 0.075');
    $content .= pdf_rect(24, 30, 547, 782, '0.045 0.065 0.125');

    // Header logos and title
    if (isset($imageNames['LogoSan'])) $content .= pdf_draw_image($imageNames['LogoSan'], 48, 760, 42, 42); else $content .= pdf_rect(48, 760, 42, 42, '0.95 1 0.92');
    if (isset($imageNames['LogoCiclon'])) $content .= pdf_draw_image($imageNames['LogoCiclon'], 100, 760, 42, 42); else $content .= pdf_rect(100, 760, 42, 42, '1 0.62 0.16');
    if (isset($imageNames['LogoCasona'])) $content .= pdf_draw_image($imageNames['LogoCasona'], 152, 760, 42, 42); else $content .= pdf_rect(152, 760, 42, 42, '0.93 0.94 0.96');
    $content .= pdf_text(404, 786, 9, 'ACCESO OFICIAL', '1 0.78 0.20');
    $content .= pdf_text(340, 762, 24, 'ENTRADA DIGITAL', '1 1 1');

    // Hero image block
    if (isset($imageNames['Hero'])) $content .= pdf_draw_image($imageNames['Hero'], 24, 550, 547, 185);
    else $content .= pdf_rect(24, 550, 547, 185, '0.11 0.15 0.28');
    $content .= pdf_rect(24, 550, 547, 65, '0.025 0.035 0.075');
    $content .= pdf_text(42, 590, 9, 'BINGO · KARAOKE · TRIBUTO · FIESTA BAILABLE', '1 0.78 0.20');
    $content .= pdf_text(42, 568, 22, 'FIESTA OCHENTERA SOLIDARIA', '1 1 1');
    $content .= pdf_text(42, 548, 19, 'TRIBUTO A ABBA', '0.22 0.87 1');
    $content .= pdf_rect(462, 680, 78, 28, '0.42 0.95 0.48');
    $content .= pdf_text(480, 690, 10, 'ACTIVA', '0.03 0.08 0.04');

    // Info cards
    $content .= pdf_rect(24, 500, 250, 42, '0.055 0.078 0.145');
    $content .= pdf_rect(286, 500, 285, 42, '0.055 0.078 0.145');
    $content .= pdf_text(42, 526, 8, 'FECHA Y HORA', '0.64 0.70 0.80');
    $content .= pdf_text(42, 510, 11, 'Vie. 24 julio 2026 · 21:00 - 05:00 hrs.', '1 1 1');
    $content .= pdf_text(304, 526, 8, 'LUGAR', '0.64 0.70 0.80');
    $content .= pdf_text(304, 510, 11, 'Club La Casona · Los Angeles', '1 1 1');

    // Main attendee card
    $content .= pdf_rect(24, 145, 332, 335, '0.055 0.078 0.145');
    $content .= pdf_text(46, 454, 8, 'TITULAR', '0.22 0.87 1');
    $content .= pdf_text(46, 430, 19, strtoupper($holder), '1 1 1');
    $content .= pdf_text(46, 386, 8, 'RUT', '0.64 0.70 0.80');
    $content .= pdf_text(236, 386, 9, (string)$reservation['rut'], '1 1 1');
    $content .= pdf_rect(46, 374, 285, 1, '0.64 0.70 0.80');
    $content .= pdf_text(46, 346, 8, 'CORREO', '0.64 0.70 0.80');
    $content .= pdf_text(180, 346, 9, (string)$reservation['email'], '1 1 1');
    $content .= pdf_rect(46, 334, 285, 1, '0.64 0.70 0.80');
    $content .= pdf_text(46, 306, 8, 'FOLIO', '0.64 0.70 0.80');
    $content .= pdf_text(212, 306, 9, $ticketCode, '1 1 1');
    $content .= pdf_rect(46, 294, 285, 1, '0.64 0.70 0.80');
    $content .= pdf_rect(46, 214, 285, 58, '1 0.72 0.18');
    $content .= pdf_text(72, 236, 34, (string)$people, '0.03 0.05 0.09');
    $content .= pdf_text(126, 240, 12, 'PERSONAS AUTORIZADAS', '0.03 0.05 0.09');
    $content .= pdf_text(46, 176, 8, 'Entrada aprobada y emitida para el titular.', '0.80 0.86 0.95');
    $content .= pdf_text(46, 158, 8, 'Presentar el codigo QR en el acceso.', '0.80 0.86 0.95');

    // QR card
    $content .= pdf_rect(370, 145, 201, 335, '0.96 0.98 1');
    if ($token !== '') $content .= pdf_qr(414, 420, 4.8, $token);
    $content .= pdf_text(426, 246, 10, 'ESCANEAR AL INGRESAR', '0.05 0.08 0.14');
    $content .= pdf_text(432, 224, 8, $ticketCode, '0.45 0.50 0.58');
    $content .= pdf_rect(414, 180, 114, 24, '0.88 0.92 0.97');
    $content .= pdf_text(438, 188, 8, 'FUERA DEL EVENTO', '0.35 0.40 0.50');
    $content .= pdf_text(426, 122, 9, 'QR unico y seguro', '0.05 0.08 0.14');
    $content .= pdf_text(414, 106, 8, 'Validacion en linea por scanner', '0.45 0.50 0.58');

    // Important footer
    $content .= pdf_rect(24, 62, 547, 54, '0.055 0.078 0.145');
    $content .= pdf_text(44, 94, 8, 'IMPORTANTE', '1 1 1');
    $content .= pdf_text(44, 76, 8, 'Muestra esta entrada desde tu celular. El personal validara el QR y registrara el ingreso del grupo.', '0.82 0.89 1');
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
    $imageNames = [];
    $nextId = 4;
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
        $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >>' . $xObject . ' >> /Contents ' . $contentId . ' 0 R >>';
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
$module = 'usuarios';
if ($adminPos !== false) {
    $modulePath = trim(substr($path, $adminPos + strlen('/admin')), '/');
    $module = $modulePath === '' ? 'usuarios' : (strtok($modulePath, '/') ?: 'usuarios');
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
} catch (Throwable $exception) {
    error_log('[admin-panel] ' . $exception::class . ': ' . $exception->getMessage());
    // La vista muestra una advertencia y datos vacíos para no romper el panel si falta la BD.
}

extract($panelData, EXTR_SKIP);
require __DIR__ . '/../app/Views/admin/dashboard.php';
