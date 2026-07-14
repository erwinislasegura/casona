<?php

require_once __DIR__ . '/ReservationRepository.php';

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
        $settings = $this->settings();
        $pending = (int)$this->db->query("SELECT COUNT(*) FROM reservas WHERE status = 'pending'")->fetchColumn();
        $tickets = (int)$this->db->query('SELECT COUNT(*) FROM entradas')->fetchColumn();
        $entered = (int)$this->db->query("SELECT COUNT(*) FROM entradas WHERE status = 'entered'")->fetchColumn();

        return [
            'stats' => [
                ['label' => 'Solicitudes', 'value' => (string)$pending, 'hint' => 'Pendientes'],
                ['label' => 'Entradas', 'value' => (string)$tickets, 'hint' => 'Emitidas'],
                ['label' => 'Validadas', 'value' => (string)$entered, 'hint' => 'En puerta'],
            ],
            'reservas' => $this->reservas(),
            'entradas' => $this->entradas(),
            'settings' => $settings,
            'dbWarning' => '',
        ];
    }

    public function reservas(): array
    {
        return $this->db->query("SELECT id, request_code, full_name, rut, phone, email, people_count, total_amount, status, receipt_path, receipt_name, created_at FROM reservas ORDER BY created_at DESC LIMIT 50")->fetchAll();
    }

    public function entradas(): array
    {
        return $this->db->query("SELECT e.id, e.status, e.issued_at, e.entered_at, r.full_name, r.request_code FROM entradas e JOIN reservas r ON r.id = e.reserva_id ORDER BY e.issued_at DESC LIMIT 12")->fetchAll();
    }

    public function settings(): array
    {
        $rows = $this->db->query('SELECT setting_key, setting_value FROM admin_settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings + ['event_name' => 'Fiesta Ochentera Solidaria', 'sales_mode' => 'Reservas con confirmación', 'notifications' => 'Activas'];
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

        $existing = $this->db->prepare("SELECT COUNT(*) FROM entradas WHERE reserva_id = :id AND status <> 'void'");
        $existing->execute(['id' => $id]);
        $missing = max(0, (int)$row['people_count'] - (int)$existing->fetchColumn());
        if ($missing === 0) return;

        $insert = $this->db->prepare("INSERT INTO entradas (ticket_code, reserva_id, holder_name, qr_token_hash, status) VALUES (:ticket_code, :reserva_id, :holder_name, :qr_token_hash, 'issued')");
        for ($i = 1; $i <= $missing; $i++) {
            $ticketCode = $row['request_code'] . '-' . str_pad((string)(((int)$row['people_count'] - $missing) + $i), 2, '0', STR_PAD_LEFT);
            $token = $ticketCode . '-' . bin2hex(random_bytes(16));
            $insert->execute([
                'ticket_code' => $ticketCode,
                'reserva_id' => $id,
                'holder_name' => $row['full_name'],
                'qr_token_hash' => hash('sha256', $token),
            ]);
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
        $allowed = ['event_name', 'sales_mode', 'notifications'];
        $stmt = $this->db->prepare('INSERT INTO admin_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($allowed as $key) {
            $stmt->execute(['k' => $key, 'v' => trim((string)($input[$key] ?? ''))]);
        }
    }

    public function validateTicket(string $token, int $adminId): array
    {
        $hash = hash('sha256', trim($token));
        $stmt = $this->db->prepare('SELECT e.id, e.status, r.full_name, r.request_code FROM entradas e JOIN reservas r ON r.id = e.reserva_id WHERE e.qr_token_hash = :hash LIMIT 1');
        $stmt->execute(['hash' => $hash]);
        $ticket = $stmt->fetch();
        if (!$ticket) return ['ok' => false, 'message' => 'Entrada no encontrada.'];
        if ($ticket['status'] === 'entered') return ['ok' => false, 'message' => 'Entrada ya validada para ' . $ticket['full_name'] . '.'];
        if ($ticket['status'] !== 'issued') return ['ok' => false, 'message' => 'Entrada no válida: ' . $ticket['status'] . '.'];
        $update = $this->db->prepare("UPDATE entradas SET status = 'entered', entered_at = NOW(), scanned_by = :admin WHERE id = :id");
        $update->execute(['admin' => $adminId, 'id' => $ticket['id']]);
        return ['ok' => true, 'message' => 'Entrada validada: ' . $ticket['full_name'] . ' · ' . $ticket['request_code']];
    }
}
