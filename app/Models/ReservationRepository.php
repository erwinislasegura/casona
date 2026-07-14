<?php

final class ReservationRepository
{
    private const TICKET_PRICE = 6000;

    public function __construct(private PDO $db) {}

    public function ensureReservationTables(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS reservas (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            request_code VARCHAR(32) NOT NULL,
            full_name VARCHAR(160) NOT NULL,
            rut VARCHAR(20) NOT NULL,
            phone VARCHAR(32) NOT NULL,
            email VARCHAR(190) NOT NULL,
            people_count INT UNSIGNED NOT NULL DEFAULT 1,
            total_amount INT UNSIGNED NOT NULL,
            status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
            receipt_path VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_reservas_request_code (request_code),
            KEY idx_reservas_status_created (status, created_at),
            KEY idx_reservas_email (email)
        ) ENGINE=InnoDB");
        $this->db->exec("CREATE TABLE IF NOT EXISTS entradas (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_code VARCHAR(40) NULL,
            reserva_id BIGINT UNSIGNED NOT NULL,
            holder_name VARCHAR(160) NULL,
            qr_token_hash CHAR(64) NOT NULL,
            status ENUM('issued','entered','exited','void') NOT NULL DEFAULT 'issued',
            issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            entered_at DATETIME NULL,
            exited_at DATETIME NULL,
            scanned_by BIGINT UNSIGNED NULL,
            UNIQUE KEY uq_entradas_qr_token_hash (qr_token_hash),
            KEY idx_entradas_ticket_code (ticket_code),
            KEY idx_entradas_status (status),
            KEY idx_entradas_reserva_id (reserva_id)
        ) ENGINE=InnoDB");
    }

    public function create(array $input, ?array $receipt): array
    {
        $this->ensureReservationTables();
        $data = $this->validate($input, $receipt);
        $data['request_code'] = $this->newRequestCode();
        $data['receipt_path'] = $receipt ? $this->storeReceipt($receipt, $data['request_code']) : null;

        $stmt = $this->db->prepare('INSERT INTO reservas (request_code, full_name, rut, phone, email, people_count, total_amount, status, receipt_path) VALUES (:request_code, :full_name, :rut, :phone, :email, :people_count, :total_amount, :status, :receipt_path)');
        $stmt->execute([
            'request_code' => $data['request_code'],
            'full_name' => $data['full_name'],
            'rut' => $data['rut'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'people_count' => $data['people_count'],
            'total_amount' => $data['total_amount'],
            'status' => 'pending',
            'receipt_path' => $data['receipt_path'],
        ]);

        return $data;
    }

    private function validate(array $input, ?array $receipt): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $rut = trim((string)($input['rut'] ?? ''));
        $phone = trim((string)($input['phone'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $people = max(1, min(20, (int)($input['people'] ?? 1)));

        if (strlen($name) < 4 || strlen($name) > 160) throw new InvalidArgumentException('Ingresa tu nombre completo.');
        if (!preg_match('/^[0-9.\-kK]{8,12}$/', $rut)) throw new InvalidArgumentException('Ingresa un RUT válido.');
        if (!preg_match('/^(?:\+?56)?\s*9[0-9\s()\-]{8,12}$/', $phone)) throw new InvalidArgumentException('Ingresa un teléfono válido.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Ingresa un correo electrónico válido.');
        if (!$receipt || ($receipt['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Adjunta el comprobante de transferencia.');
        if (($receipt['size'] ?? 0) > 8 * 1024 * 1024) throw new InvalidArgumentException('El comprobante no puede superar 8 MB.');

        return [
            'full_name' => $name,
            'rut' => $rut,
            'phone' => $phone,
            'email' => $email,
            'people_count' => $people,
            'total_amount' => $people * self::TICKET_PRICE,
        ];
    }

    private function newRequestCode(): string
    {
        do {
            $code = 'FO-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM reservas WHERE request_code = :code');
            $stmt->execute(['code' => $code]);
        } while ((int)$stmt->fetchColumn() > 0);
        return $code;
    }

    private function storeReceipt(array $receipt, string $code): string
    {
        $extension = strtolower(pathinfo((string)$receipt['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($extension, $allowed, true)) throw new InvalidArgumentException('El comprobante debe ser imagen o PDF.');

        $publicDir = dirname(__DIR__, 2) . '/public/storage/comprobantes';
        if (!is_dir($publicDir)) mkdir($publicDir, 0775, true);
        $fileName = $code . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $target = $publicDir . '/' . $fileName;
        if (!move_uploaded_file((string)$receipt['tmp_name'], $target)) throw new RuntimeException('No fue posible guardar el comprobante.');

        $rootDir = dirname(__DIR__, 2) . '/storage/comprobantes';
        if (!is_dir($rootDir)) mkdir($rootDir, 0775, true);
        $rootTarget = $rootDir . '/' . $fileName;
        if ($rootTarget !== $target) @copy($target, $rootTarget);

        return '/storage/comprobantes/' . $fileName;
    }
}
