<?php

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
        return $this->db->query("SELECT id, request_code, full_name, people_count, total_amount, status, created_at FROM reservas ORDER BY created_at DESC LIMIT 12")->fetchAll();
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

    public function updateReservaStatus(int $id, string $status): void
    {
        if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) return;
        $stmt = $this->db->prepare('UPDATE reservas SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
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
