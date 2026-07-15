<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../app/Models/AdminPanelRepository.php';
require_once __DIR__ . '/../app/Support/TicketSupport.php';

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
    $ticketCode = (string)($ticket['ticket_code'] ?: ($reservation['request_code'] ?? ('Ticket #' . ($ticket['id'] ?? 0))));
    $holder = mb_substr((string)($ticket['holder_name'] ?: $reservation['full_name']), 0, 30);
    $email = mb_substr((string)($reservation['email'] ?? ''), 0, 34);
    $token = (string)($ticket['qr_token'] ?? '');
    $people = max(1, (int)$reservation['people_count']);
    $status = ($ticket['status'] ?? '') === 'entered' ? 'USADA' : 'VALIDA';
    $content = '';

    // Diseño implementado desde entrada.html: tarjeta vertical 95mm x 180mm.
    $content .= pdf_rect(0, 0, 269.29, 510.24, '0.933 0.945 0.961');
    $content .= pdf_rect(0, 0, 269.29, 510.24, '1 1 1');

    // Header azul con acentos neón.
    $content .= pdf_rect(0, 350, 269.29, 160.24, '0.027 0.043 0.094');
    $content .= pdf_rect(0, 350, 269.29, 58, '0.095 0.149 0.325');
    $content .= pdf_rect(198, 430, 94, 94, '1.000 0.310 0.722');
    $content .= pdf_rect(208, 440, 74, 74, '0.027 0.043 0.094');

    // Logos circulares aproximados al template.
    $logoY = 438;
    foreach ([['LogoSan', 16], ['LogoCiclon', 55], ['LogoCasona', 94]] as $logo) {
        $content .= pdf_rect($logo[1], $logoY, 32, 32, '1 1 1');
        if (isset($imageNames[$logo[0]])) $content .= pdf_draw_image($imageNames[$logo[0]], $logo[1] + 3, $logoY + 3, 26, 26);
    }
    $content .= pdf_text(138, 460, 6, 'ENTRADA VIRTUAL', '0.824 0.859 0.918', 'F2');
    $content .= pdf_text(138, 446, 11, 'FIESTA OCHENTERA', '1 1 1', 'F2');

    $content .= pdf_rect(18, 398, 92, 18, '1.000 0.812 0.361');
    $content .= pdf_text(27, 404, 7, 'ACCESO APROBADO', '0.082 0.102 0.176', 'F2');
    $content .= pdf_text(18, 372, 18, 'FIESTA OCHENTERA', '1 1 1', 'F2');
    $content .= pdf_text(18, 352, 14, 'SOLIDARIA', '0.224 0.875 1.000', 'F2');

    // Estado.
    $content .= pdf_rect(18, 318, 233, 28, '0.953 0.961 0.973');
    $content .= pdf_text(30, 328, 7, 'ESTADO DE LA ENTRADA', '0.400 0.439 0.522', 'F2');
    $content .= pdf_rect(205, 323, 34, 14, $status === 'VALIDA' ? '0.863 0.988 0.906' : '0.859 0.929 1.000');
    $content .= pdf_text(211, 327, 6, $status, $status === 'VALIDA' ? '0.043 0.478 0.196' : '0.090 0.365 0.827', 'F2');

    $rows = [
        ['Nombre', strtoupper($holder)],
        ['Correo', $email],
        ['Personas', $people . ' autorizadas'],
        ['Fecha', 'Viernes 24 de julio de 2026'],
        ['Hora', 'Desde las 21:00 horas'],
        ['Lugar', 'Club La Casona - Los Angeles'],
        ['Ticket', $ticketCode],
    ];
    $y = 292;
    foreach ($rows as $row) {
        $content .= '0.815 0.835 0.867 RG 0.5 w [3 3] 0 d 18 ' . ($y - 8) . ' m 251 ' . ($y - 8) . " l S [] 0 d\n";
        $content .= pdf_text(18, $y, 6, strtoupper($row[0]), '0.400 0.439 0.522', 'F2');
        $content .= pdf_text(96, $y, 8, mb_substr((string)$row[1], 0, 34), '0.063 0.094 0.157', 'F2');
        $y -= 25;
    }

    $content .= '0.720 0.753 0.800 RG 0.8 w [5 5] 0 d 0 128 m 269.29 128 l S [] 0 d' . "\n";
    $content .= pdf_rect(81, 154, 108, 108, '1 1 1');
    $content .= '0.815 0.835 0.867 RG 0.8 w 81 154 108 108 re S' . "\n";
    if ($token !== '') $content .= pdf_qr(98, 242, 2.55, $token);
    $content .= pdf_text(83, 136, 8, mb_substr($ticketCode, 0, 24), '0.095 0.149 0.325', 'F2');
    $content .= pdf_rect(101, 116, 68, 13, '0.918 0.949 1.000');
    $content .= pdf_text(111, 120, 6, 'FUERA DEL EVENTO', '0.090 0.365 0.827', 'F2');
    $content .= pdf_text(42, 92, 7, 'Presenta este codigo QR al ingresar. El personal autorizado', '0.400 0.439 0.522');
    $content .= pdf_text(55, 80, 7, 'registrara el ingreso o la salida de la entrada.', '0.400 0.439 0.522');

    $content .= pdf_rect(0, 0, 269.29, 46, '0.973 0.980 0.988');
    $content .= pdf_text(66, 25, 8, 'Fiesta Ochentera Solidaria', '0.095 0.149 0.325', 'F2');
    $content .= pdf_text(43, 12, 7, 'Contacto: +56 9 5627 1248 - casona.gocreative.cl', '0.400 0.439 0.522');
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
        $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 269.29 510.24] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>' . $xObject . ' >> /Contents ' . $contentId . ' 0 R >>';
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
            $_SESSION['admin_flash'] = ((string)($_POST['status'] ?? '') === 'approved') ? 'Registro aprobado; se creó la entrada pública y se envió el correo HTML.' : 'Registro actualizado correctamente.';
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
