<?php

require_once __DIR__ . '/ReservationRepository.php';
require_once __DIR__ . '/../Support/TicketSupport.php';

final class AdminPanelRepository
{
    public function __construct(private PDO $db) {}

    public static function fallbackData(): array
    {
        return [
            'stats' => [
                ['label' => 'Solicitudes', 'value' => '0', 'hint' => 'Sin conexión BD'],
                ['label' => 'Entradas', 'value' => '0', 'hint' => 'Sin conexión BD'],
                ['label' => 'Validadas', 'value' => '0', 'hint' => 'Sin conexión BD'],
            ],
            'reservas' => [],
            'entradas' => [],
            'settings' => ['event_name' => 'Fiesta Ochentera Solidaria', 'sales_mode' => 'Reservas con confirmación', 'notifications' => 'Activas'],
            'adminUsers' => [],
            'roleOptions' => self::roleOptions(),
            'dbWarning' => 'No se pudo conectar a la base de datos. Revisa config/database.php y ejecuta database/schema.sql.',
        ];
    }

    public function ensurePanelTables(): void
    {
        (new ReservationRepository($this->db))->ensureReservationTables();
        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_settings (setting_key VARCHAR(80) PRIMARY KEY, setting_value TEXT NOT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB");
        $this->db->exec("INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('event_name','Fiesta Ochentera Solidaria'),('sales_mode','Reservas con confirmación'),('notifications','Activas')");
    }

    public function dashboardData(): array
    {
        $this->ensurePanelTables();
        $this->ensureAdminUsersTable();
        $pending = (int)$this->db->query("SELECT COUNT(*) FROM reservas WHERE status = 'pending'")->fetchColumn();
        $approved = (int)$this->db->query("SELECT COUNT(*) FROM reservas WHERE status = 'approved'")->fetchColumn();
        $rejected = (int)$this->db->query("SELECT COUNT(*) FROM reservas WHERE status = 'rejected'")->fetchColumn();

        return [
            'stats' => [
                ['label' => 'Pendientes', 'value' => (string)$pending, 'hint' => 'Por revisar'],
                ['label' => 'Aprobadas', 'value' => (string)$approved, 'hint' => 'Con entrada pública'],
                ['label' => 'Rechazadas', 'value' => (string)$rejected, 'hint' => 'No aprobadas'],
            ],
            'reservas' => $this->reservas(),
            'entradas' => $this->entradas(),
            'settings' => $this->settings(),
            'adminUsers' => $this->adminUsers(),
            'roleOptions' => self::roleOptions(),
            'dbWarning' => '',
        ];
    }

    public function reservas(?string $status = null): array
    {
        if ($status !== null && in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $stmt = $this->db->prepare("SELECT r.id, r.request_code, r.full_name, r.rut, r.phone, r.email, r.people_count, r.total_amount, r.status, r.receipt_path, r.receipt_name, r.created_at, (SELECT e.qr_token FROM entradas e WHERE e.reserva_id = r.id AND e.status <> 'void' ORDER BY e.id ASC LIMIT 1) AS qr_token FROM reservas r WHERE status = :status ORDER BY created_at DESC LIMIT 50");
            $stmt->execute(['status' => $status]);
            return $stmt->fetchAll();
        }

        return $this->db->query("SELECT r.id, r.request_code, r.full_name, r.rut, r.phone, r.email, r.people_count, r.total_amount, r.status, r.receipt_path, r.receipt_name, r.created_at, (SELECT e.qr_token FROM entradas e WHERE e.reserva_id = r.id AND e.status <> 'void' ORDER BY e.id ASC LIMIT 1) AS qr_token FROM reservas r ORDER BY created_at DESC LIMIT 50")->fetchAll();
    }

    public function entradas(): array
    {
        return $this->db->query("SELECT e.id, e.status, e.issued_at, e.entered_at, r.full_name, r.request_code, r.people_count FROM entradas e JOIN reservas r ON r.id = e.reserva_id WHERE e.status <> 'void' ORDER BY e.issued_at DESC LIMIT 12")->fetchAll();
    }

    public function settings(): array
    {
        $this->ensureSettingsTable();
        $rows = $this->db->query('SELECT setting_key, setting_value FROM admin_settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings + ['event_name' => 'Fiesta Ochentera Solidaria', 'sales_mode' => 'Reservas con confirmación', 'notifications' => 'Activas'];
    }



    public function reservaWithTickets(int $id): ?array
    {
        (new ReservationRepository($this->db))->ensureReservationTables();
        $stmt = $this->db->prepare('SELECT id, request_code, full_name, rut, phone, email, people_count, total_amount, status, created_at FROM reservas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $reservation = $stmt->fetch();
        if (!$reservation) return null;

        if ($reservation['status'] === 'approved') {
            $this->issueTicketsForReservation($id);
        }

        $this->ensureTicketTokens($id);

        $tickets = $this->db->prepare("SELECT id, ticket_code, holder_name, qr_token, status, issued_at FROM entradas WHERE reserva_id = :id AND status <> 'void' ORDER BY id ASC LIMIT 1");
        $tickets->execute(['id' => $id]);
        $reservation['tickets'] = $tickets->fetchAll();
        return $reservation;
    }


    public function publicTicketByToken(string $token): ?array
    {
        (new ReservationRepository($this->db))->ensureReservationTables();
        $hash = hash('sha256', trim($token));
        $stmt = $this->db->prepare("SELECT r.id, r.request_code, r.full_name, r.email, r.people_count, r.status AS reservation_status, e.id AS ticket_id, e.ticket_code, e.holder_name, e.qr_token, e.status, e.issued_at FROM entradas e JOIN reservas r ON r.id = e.reserva_id WHERE e.qr_token_hash = :hash AND e.status <> 'void' LIMIT 1");
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch();
        if (!$row || $row['reservation_status'] !== 'approved') return null;
        return [
            'reservation' => ['id' => $row['id'], 'request_code' => $row['request_code'], 'full_name' => $row['full_name'], 'email' => $row['email'], 'people_count' => $row['people_count'], 'status' => $row['reservation_status']],
            'ticket' => ['id' => $row['ticket_id'], 'ticket_code' => $row['ticket_code'], 'holder_name' => $row['holder_name'], 'qr_token' => $row['qr_token'], 'status' => $row['status'], 'issued_at' => $row['issued_at']],
        ];
    }

    private function sendTicketEmail(int $id): void
    {
        $reservation = $this->reservaWithTickets($id);
        if (!$reservation || empty($reservation['email']) || empty($reservation['tickets'][0]['qr_token'])) return;
        $ticket = $reservation['tickets'][0];
        $ticketUrl = ticket_public_url((string)$ticket['qr_token']);
        $subject = 'Tu entrada Fiesta Ochentera Solidaria';
        $body = ticket_html($reservation, $ticket, $ticketUrl, true);
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Fiesta Ochentera <no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>',
        ];
        @mail((string)$reservation['email'], $subject, $body, implode("\r\n", $headers));
    }

    public function reservaReceipt(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT receipt_path, receipt_name, receipt_mime, receipt_blob FROM reservas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        if (!empty($row['receipt_blob'])) {
            return [
                'name' => $row['receipt_name'] ?: 'comprobante',
                'mime' => $row['receipt_mime'] ?: 'application/octet-stream',
                'content' => $row['receipt_blob'],
            ];
        }
        if (!empty($row['receipt_path'])) {
            foreach ([dirname(__DIR__, 2) . '/public' . $row['receipt_path'], dirname(__DIR__, 2) . $row['receipt_path']] as $file) {
                if (is_file($file)) {
                    return [
                        'name' => basename($file),
                        'mime' => mime_content_type($file) ?: 'application/octet-stream',
                        'content' => file_get_contents($file),
                    ];
                }
            }
        }
        return null;
    }

    public function updateReservaStatus(int $id, string $status): void
    {
        (new ReservationRepository($this->db))->ensureReservationTables();
        if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) return;
        $stmt = $this->db->prepare('UPDATE reservas SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        if ($status === 'approved') {
            $this->issueTicketsForReservation($id);
            $this->sendTicketEmail($id);
        } elseif (in_array($status, ['rejected', 'cancelled'], true)) {
            $void = $this->db->prepare("UPDATE entradas SET status = 'void' WHERE reserva_id = :id AND status = 'issued'");
            $void->execute(['id' => $id]);
        }
    }

    private function issueTicketsForReservation(int $id): void
    {
        $reservation = $this->db->prepare('SELECT id, request_code, full_name, people_count FROM reservas WHERE id = :id LIMIT 1');
        $reservation->execute(['id' => $id]);
        $row = $reservation->fetch();
        if (!$row) return;

        $existing = $this->db->prepare("SELECT id FROM entradas WHERE reserva_id = :id AND status <> 'void' ORDER BY id ASC");
        $existing->execute(['id' => $id]);
        $activeTickets = $existing->fetchAll();

        if (count($activeTickets) > 1) {
            $keepId = (int)$activeTickets[0]['id'];
            $voidExtra = $this->db->prepare("UPDATE entradas SET status = 'void' WHERE reserva_id = :reserva_id AND id <> :keep_id AND status <> 'void'");
            $voidExtra->execute(['reserva_id' => $id, 'keep_id' => $keepId]);
        }

        if (!empty($activeTickets)) {
            $this->ensureTicketTokens($id);
            return;
        }

        $ticketCode = $row['request_code'] . '-GRUPO';
        $token = $ticketCode . '-' . bin2hex(random_bytes(12));
        $insert = $this->db->prepare("INSERT INTO entradas (ticket_code, reserva_id, holder_name, qr_token, qr_token_hash, status) VALUES (:ticket_code, :reserva_id, :holder_name, :qr_token, :qr_token_hash, 'issued')");
        $insert->execute([
            'ticket_code' => $ticketCode,
            'reserva_id' => $id,
            'holder_name' => $row['full_name'],
            'qr_token' => $token,
            'qr_token_hash' => hash('sha256', $token),
        ]);
    }



    private function ensureTicketTokens(int $reservaId): void
    {
        $tickets = $this->db->prepare('SELECT id, ticket_code FROM entradas WHERE reserva_id = :id AND (qr_token IS NULL OR qr_token = \'\')');
        $tickets->execute(['id' => $reservaId]);
        $update = $this->db->prepare('UPDATE entradas SET qr_token = :token, qr_token_hash = :hash WHERE id = :id');
        foreach ($tickets->fetchAll() as $ticket) {
            $token = ($ticket['ticket_code'] ?: ('TICKET-' . $ticket['id'])) . '-' . bin2hex(random_bytes(12));
            $update->execute(['token' => $token, 'hash' => hash('sha256', $token), 'id' => $ticket['id']]);
        }
    }

    public function updateReservaDetails(int $id, array $input): void
    {
        (new ReservationRepository($this->db))->ensureReservationTables();
        $people = max(1, min(20, (int)($input['people_count'] ?? 1)));
        $total = $people * 6000;
        $stmt = $this->db->prepare('UPDATE reservas SET full_name = :full_name, rut = :rut, phone = :phone, email = :email, people_count = :people_count, total_amount = :total_amount WHERE id = :id');
        $stmt->execute([
            'full_name' => trim((string)($input['full_name'] ?? '')),
            'rut' => trim((string)($input['rut'] ?? '')),
            'phone' => trim((string)($input['phone'] ?? '')),
            'email' => trim((string)($input['email'] ?? '')),
            'people_count' => $people,
            'total_amount' => $total,
            'id' => $id,
        ]);
    }

    public function deleteReserva(int $id): void
    {
        (new ReservationRepository($this->db))->ensureReservationTables();
        $stmt = $this->db->prepare('SELECT receipt_path FROM reservas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $receiptPath = (string)($stmt->fetchColumn() ?: '');

        $this->db->prepare('DELETE FROM entradas WHERE reserva_id = :id')->execute(['id' => $id]);
        $this->db->prepare('DELETE FROM reservas WHERE id = :id')->execute(['id' => $id]);

        if ($receiptPath !== '' && str_starts_with($receiptPath, '/storage/comprobantes/')) {
            foreach ([dirname(__DIR__, 2) . '/public' . $receiptPath, dirname(__DIR__, 2) . $receiptPath] as $file) {
                if (is_file($file)) @unlink($file);
            }
        }
    }

    public function saveSettings(array $input): void
    {
        $this->ensureSettingsTable();
        $allowed = ['event_name', 'sales_mode', 'notifications'];
        $stmt = $this->db->prepare('INSERT INTO admin_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($allowed as $key) {
            $stmt->execute(['k' => $key, 'v' => trim((string)($input[$key] ?? ''))]);
        }
    }

    public function validateTicket(string $token, int $adminId): array
    {
        $hash = hash('sha256', trim($token));
        $stmt = $this->db->prepare('SELECT e.id, e.status, r.full_name, r.request_code, r.people_count FROM entradas e JOIN reservas r ON r.id = e.reserva_id WHERE e.qr_token_hash = :hash LIMIT 1');
        $stmt->execute(['hash' => $hash]);
        $ticket = $stmt->fetch();
        if (!$ticket) return ['ok' => false, 'message' => 'Entrada no encontrada.'];
        if ($ticket['status'] === 'entered') return ['ok' => false, 'message' => 'Entrada ya validada para ' . $ticket['full_name'] . '.'];
        if ($ticket['status'] !== 'issued') return ['ok' => false, 'message' => 'Entrada no válida: ' . $ticket['status'] . '.'];
        $update = $this->db->prepare("UPDATE entradas SET status = 'entered', entered_at = NOW(), scanned_by = :admin WHERE id = :id");
        $update->execute(['admin' => $adminId, 'id' => $ticket['id']]);
        return ['ok' => true, 'message' => 'Entrada validada: ' . $ticket['full_name'] . ' · ' . $ticket['request_code'] . ' · válida para ' . (int)$ticket['people_count'] . ' persona(s).'];
    }


    private function ensureSettingsTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_settings (setting_key VARCHAR(80) PRIMARY KEY, setting_value TEXT NOT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB");
        $this->db->exec("INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('event_name','Fiesta Ochentera Solidaria'),('sales_mode','Reservas con confirmación'),('notifications','Activas')");
    }


    public static function roleOptions(): array
    {
        return ['admin' => 'Administrador', 'scanner' => 'Validador', 'viewer' => 'Lectura'];
    }

    public function adminUsers(): array
    {
        $this->ensureAdminUsersTable();
        return $this->db->query('SELECT id, name, username, email, role, is_active, last_login_at, created_at FROM admin_users ORDER BY name ASC')->fetchAll();
    }

    public function saveAdminUser(array $input, ?int $id = null): void
    {
        $this->ensureAdminUsersTable();
        $role = (string)($input['role'] ?? 'viewer');
        if (!array_key_exists($role, self::roleOptions())) $role = 'viewer';
        $data = [
            'name' => trim((string)($input['name'] ?? '')),
            'username' => mb_strtolower(trim((string)($input['username'] ?? ''))),
            'email' => mb_strtolower(trim((string)($input['email'] ?? ''))),
            'role' => $role,
            'is_active' => (int)($input['is_active'] ?? 1) === 1 ? 1 : 0,
        ];
        $password = trim((string)($input['password'] ?? ''));
        if ($id) {
            $sql = 'UPDATE admin_users SET name = :name, username = :username, email = :email, role = :role, is_active = :is_active';
            if ($password !== '') {
                $sql .= ', password_hash = :password_hash, failed_login_count = 0, locked_until = NULL';
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $data['id'] = $id;
            $this->db->prepare($sql)->execute($data);
            return;
        }
        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $this->db->prepare('INSERT INTO admin_users (name, username, email, password_hash, role, is_active) VALUES (:name, :username, :email, :password_hash, :role, :is_active)')->execute($data);
    }

    private function ensureAdminUsersTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_users (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, username VARCHAR(60) NOT NULL, email VARCHAR(190) NOT NULL, password_hash VARCHAR(255) NOT NULL, role ENUM('admin','scanner','viewer') NOT NULL DEFAULT 'viewer', is_active TINYINT(1) NOT NULL DEFAULT 1, last_login_at DATETIME NULL, last_login_ip VARBINARY(16) NULL, failed_login_count INT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_admin_users_username (username), UNIQUE KEY uq_admin_users_email (email), KEY idx_admin_users_active (is_active), KEY idx_admin_users_locked_until (locked_until)) ENGINE=InnoDB");
    }
}
