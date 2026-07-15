<?php

function ticket_public_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . app_base_path();
}

function ticket_public_url(string $token): string
{
    return ticket_public_base_url() . '/entrada/?token=' . rawurlencode($token);
}

function ticket_qr_svg(string $text, int $scale = 5): string
{
    $matrix = function_exists('qr_matrix') ? qr_matrix($text) : [];
    if (!$matrix) return '';
    $size = count($matrix);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . ($size * $scale) . '" height="' . ($size * $scale) . '" viewBox="0 0 ' . $size . ' ' . $size . '"><rect width="100%" height="100%" fill="#fff"/>';
    foreach ($matrix as $y => $row) foreach ($row as $x => $dark) if ($dark) $svg .= '<rect x="' . $x . '" y="' . $y . '" width="1" height="1" fill="#000"/>';
    return $svg . '</svg>';
}

function ticket_html(array $reservation, array $ticket, string $ticketUrl, bool $email = false): string
{
    $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $ticketCode = $ticket['ticket_code'] ?: (($reservation['request_code'] ?? 'ENTRADA') . '-GRUPO');
    $status = ($ticket['status'] ?? '') === 'entered' ? 'Usada' : 'Válida';
    $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($ticketUrl);
    return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Entrada virtual</title><style>body{margin:0;background:#eef1f5;font-family:Arial,Helvetica,sans-serif;color:#101828;padding:20px}.ticket{max-width:380px;margin:auto;background:#fff;border-radius:22px;overflow:hidden;box-shadow:0 18px 45px rgba(7,11,24,.2)}.header{background:linear-gradient(135deg,#070b18,#182653);color:#fff;padding:18px}.kicker{display:inline-block;background:#ffcf5c;color:#151a2d;font-weight:900;border-radius:99px;padding:6px 10px;font-size:11px}.header h1{margin:12px 0 0;text-transform:uppercase;line-height:1}.header em{display:block;color:#39dfff;font-style:normal}.body{padding:18px}.status{display:flex;justify-content:space-between;background:#f3f5f8;border-radius:14px;padding:12px;margin-bottom:16px}.status strong{color:#0b7a32;background:#dcfce7;border-radius:99px;padding:5px 10px}.row{display:flex;justify-content:space-between;gap:16px;border-bottom:1px dashed #d0d5dd;padding:9px 0}.label{color:#667085;font-size:11px;text-transform:uppercase;font-weight:700}.value{text-align:right;font-weight:700}.qr{text-align:center;padding:18px 0}.qr img{width:160px;height:160px;border:1px solid #d0d5dd;border-radius:18px;padding:10px}.btn{display:inline-block;background:#182653;color:#fff!important;text-decoration:none;border-radius:12px;padding:12px 16px;font-weight:700}.footer{background:#f8fafc;text-align:center;padding:14px;color:#667085;font-size:12px}</style></head><body><div class="ticket"><div class="header"><span class="kicker">Acceso aprobado</span><h1>Fiesta Ochentera<em>Solidaria</em></h1></div><div class="body"><div class="status"><span>Estado de la entrada</span><strong>' . $e($status) . '</strong></div><div class="row"><span class="label">Nombre</span><span class="value">' . $e($reservation['full_name'] ?? '') . '</span></div><div class="row"><span class="label">Correo</span><span class="value">' . $e($reservation['email'] ?? '') . '</span></div><div class="row"><span class="label">Personas</span><span class="value">' . (int)($reservation['people_count'] ?? 1) . ' autorizadas</span></div><div class="row"><span class="label">Fecha</span><span class="value">Viernes 24 de julio de 2026<br>Desde las 21:00 horas</span></div><div class="row"><span class="label">Lugar</span><span class="value">Club La Casona<br>Los Ángeles</span></div><div class="row"><span class="label">Ticket</span><span class="value">' . $e($ticketCode) . '</span></div><div class="qr"><img src="' . $e($qrSrc) . '" alt="Código QR"><p><strong>' . $e($ticketCode) . '</strong></p><p><a class="btn" href="' . $e($ticketUrl) . '">Abrir entrada pública</a></p><p>Presenta este código QR al ingresar.</p></div></div><div class="footer"><strong>Fiesta Ochentera Solidaria</strong><br>Contacto: +56 9 5627 1248 · casona.gocreative.cl</div></div></body></html>';
}
