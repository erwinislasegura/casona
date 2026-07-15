<?php

function ticket_base_path(): string
{
    $base = function_exists('app_base_path') ? app_base_path() : '';
    if ($base !== '') return $base;

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    foreach (['/admin', '/entrada', '/reservas'] as $marker) {
        $pos = strpos($path, $marker);
        if ($pos !== false) return rtrim(substr($path, 0, $pos), '/');
    }
    return '';
}

function ticket_public_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . ticket_base_path();
}

function ticket_public_url(string $token): string
{
    return ticket_public_base_url() . '/entrada/?token=' . rawurlencode($token);
}

function ticket_asset_url(string $path): string
{
    return ticket_public_base_url() . '/' . ltrim($path, '/');
}

function ticket_html(array $reservation, array $ticket, string $ticketUrl, bool $email = false): string
{
    $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $ticketCode = $ticket['ticket_code'] ?: (($reservation['request_code'] ?? 'ENTRADA') . '-GRUPO');
    $status = ($ticket['status'] ?? '') === 'entered' ? 'Usada' : 'Válida';
    $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($ticketUrl);
    $logos = [
        ['src' => ticket_asset_url('assets/logo-san-gabriel.png'), 'alt' => 'San Gabriel'],
        ['src' => ticket_asset_url('assets/logo-ciclon.jpeg'), 'alt' => 'Ciclón Producciones'],
        ['src' => ticket_asset_url('assets/logo-la-casona.jpeg'), 'alt' => 'Club La Casona'],
    ];

    $logoHtml = '';
    foreach ($logos as $logo) {
        $logoHtml .= '<span class="logo-badge"><img src="' . $e($logo['src']) . '" alt="' . $e($logo['alt']) . '"></span>';
    }

    return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><title>Entrada virtual · Fiesta Ochentera</title><style>:root{--bg:#080d1a;--bg2:#0f1730;--panel:rgba(15,23,48,.82);--line:rgba(255,255,255,.14);--white:#f7fbff;--text:#d2dbea;--muted:#9fb1c7;--pink:#ff4fb8;--cyan:#38dfff;--violet:#8a63ff;--gold:#ffcf5c;--orange:#ff9a1f;--green:#4ade80;--shadow:0 28px 80px rgba(0,0,0,.35)}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--white);background:radial-gradient(circle at 15% 12%,rgba(255,79,184,.16),transparent 30rem),radial-gradient(circle at 88% 10%,rgba(56,223,255,.14),transparent 24rem),linear-gradient(180deg,#070b15 0%,#0a1020 42%,#0b1225 100%);line-height:1.55;padding:24px}body:before{content:"";position:fixed;inset:0;pointer-events:none;opacity:.12;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:42px 42px;mask-image:linear-gradient(to bottom,#000,transparent 80%)}.ticket-shell{position:relative;width:360px;max-width:100%;margin:0 auto}.topbar{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:22px;padding:12px 14px;border:1px solid var(--line);border-radius:26px;background:rgba(8,13,26,.72);backdrop-filter:blur(18px)}.logo-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.logo-badge{width:48px;height:48px;border-radius:50%;background:#fff;border:3px solid rgba(255,255,255,.14);box-shadow:0 12px 30px rgba(0,0,0,.24),inset 0 0 0 1px rgba(0,0,0,.04);display:grid;place-items:center;padding:5px;overflow:hidden;flex:0 0 auto}.logo-badge img{width:100%;height:100%;object-fit:contain;border-radius:50%;display:block}.brand-copy strong{display:block;font-size:.98rem;letter-spacing:.08em;text-transform:uppercase}.brand-copy span{display:block;color:var(--muted);font-size:.76rem;letter-spacing:.12em;text-transform:uppercase}.ticket{overflow:hidden;border:1px solid rgba(255,255,255,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.045));box-shadow:var(--shadow)}.hero{position:relative;padding:18px;background:linear-gradient(145deg,rgba(255,79,184,.13),rgba(56,223,255,.08),rgba(255,207,92,.08));isolation:isolate}.hero:before{content:"";position:absolute;right:-80px;top:-80px;width:240px;height:240px;border-radius:50%;background:rgba(56,223,255,.13);filter:blur(14px);z-index:-1}.hero:after{content:"";position:absolute;left:-70px;bottom:-70px;width:190px;height:190px;border-radius:50%;background:rgba(255,79,184,.13);filter:blur(14px);z-index:-1}.eyebrow{display:inline-flex;align-items:center;gap:10px;padding:8px 13px;border-radius:999px;font-size:.75rem;font-weight:900;text-transform:uppercase;letter-spacing:.14em;color:#ffe8a5;background:rgba(255,207,92,.08);border:1px solid rgba(255,207,92,.24)}.eyebrow i{width:8px;height:8px;border-radius:50%;background:var(--orange);box-shadow:0 0 0 7px rgba(255,154,31,.12)}h1{margin:18px 0 10px;font-size:clamp(1.55rem,8vw,2rem);line-height:1.01;letter-spacing:-.055em;text-transform:uppercase}.accent{display:block;color:var(--cyan);text-shadow:0 0 18px rgba(56,223,255,.18)}.lead{margin:0;color:var(--text);max-width:680px}.hero-brand{display:flex;align-items:center;gap:10px;margin-bottom:14px}.hero-brand .brand-copy{margin-left:2px}.hero-brand .brand-copy strong{font-size:.86rem}.hero-brand .brand-copy span{font-size:.62rem}.body{display:block;padding:18px;background:rgba(8,13,26,.55)}.status{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 16px;margin-bottom:18px;border:1px solid var(--line);border-radius:14px;background:rgba(255,255,255,.055)}.status span{color:var(--muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.14em;font-weight:900}.status strong{color:#0a1e13;background:var(--green);border-radius:999px;padding:6px 11px;text-transform:uppercase;font-size:.76rem;font-weight:1000}.info{display:grid;grid-template-columns:1fr;gap:9px}.row{min-height:auto;padding:10px 12px;border:1px solid var(--line);border-radius:14px;background:rgba(255,255,255,.055)}.label{display:block;color:var(--cyan);font-size:.72rem;text-transform:uppercase;letter-spacing:.16em;font-weight:900;margin-bottom:6px}.value{display:block;color:#f7fbff;font-weight:850;line-height:1.35}.qr-card{margin-top:16px;border:1px solid rgba(255,255,255,.16);border-radius:26px;background:rgba(9,14,28,.72);padding:20px;text-align:center;align-self:start}.qr-box{width:154px;height:154px;margin:0 auto 16px;background:#fff;border-radius:18px;padding:10px;box-shadow:0 18px 48px rgba(0,0,0,.22)}.qr-box img{width:100%;height:100%;display:block}.code{color:#ffe8a5;font-weight:1000;letter-spacing:.06em;margin-bottom:12px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 18px;border-radius:15px;background:linear-gradient(135deg,var(--gold),var(--orange));color:#12192c!important;text-decoration:none;font-weight:1000;box-shadow:0 14px 34px rgba(255,154,31,.24)}.note{margin:14px 0 0;color:var(--muted);font-size:.9rem}.footer{padding:18px 24px;text-align:center;color:var(--muted);background:rgba(255,255,255,.045);border-top:1px solid var(--line)}.footer strong{color:#fff}@media(max-width:760px){body{padding:14px}.topbar{align-items:flex-start;flex-direction:column}.logo-badge{width:56px;height:56px}.hero{padding:26px 20px}.body{grid-template-columns:1fr;padding:20px}.info{grid-template-columns:1fr}.qr-card{order:-1}.ticket{border-radius:26px}}</style></head><body><main class="ticket-shell"><article class="ticket"><section class="hero"><div class="hero-brand"><div class="logo-row">' . $logoHtml . '</div><div class="brand-copy"><strong>Fiesta Ochentera</strong><span>Entrada virtual</span></div></div><span class="eyebrow"><i></i>Acceso aprobado</span><h1>Fiesta Ochentera<span class="accent">Solidaria</span></h1><p class="lead">Entrada pública para presentar en puerta. El QR contiene este link y permite validar el acceso de forma segura.</p></section><section class="body"><div><div class="status"><span>Estado de la entrada</span><strong>' . $e($status) . '</strong></div><div class="info"><div class="row"><span class="label">Nombre</span><span class="value">' . $e($reservation['full_name'] ?? '') . '</span></div><div class="row"><span class="label">Correo</span><span class="value">' . $e($reservation['email'] ?? '') . '</span></div><div class="row"><span class="label">Personas</span><span class="value">' . (int)($reservation['people_count'] ?? 1) . ' autorizadas</span></div><div class="row"><span class="label">Fecha</span><span class="value">Viernes 24 de julio de 2026<br>Desde las 21:00 horas</span></div><div class="row"><span class="label">Lugar</span><span class="value">Club La Casona<br>Los Ángeles</span></div><div class="row"><span class="label">Ticket</span><span class="value">' . $e($ticketCode) . '</span></div></div></div><aside class="qr-card"><div class="qr-box"><img src="' . $e($qrSrc) . '" alt="Código QR de la entrada"></div><div class="code">' . $e($ticketCode) . '</div><a class="btn" href="' . $e($ticketUrl) . '">Abrir entrada pública</a><p class="note">Presenta este código QR al ingresar. El personal autorizado registrará el acceso.</p></aside></section><footer class="footer"><strong>Fiesta Ochentera Solidaria</strong><br>Contacto: +56 9 5627 1248 · casona.gocreative.cl</footer></article></main></body></html>';
}
