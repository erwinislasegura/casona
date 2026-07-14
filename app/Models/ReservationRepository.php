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
            receipt_name VARCHAR(190) NULL,
            receipt_mime VARCHAR(120) NULL,
            receipt_blob LONGBLOB NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_reservas_request_code (request_code),
            KEY idx_reservas_status_created (status, created_at),
            KEY idx_reservas_email (email)
        ) ENGINE=InnoDB");
        $this->ensureReceiptColumns();
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
        $this->ensureTicketColumns();
    }

    public function create(array $input, ?array $receipt): array
    {
        $this->ensureReservationTables();
        $data = $this->validate($input, $receipt);
        $data['request_code'] = $this->newRequestCode();
        $storedReceipt = $receipt ? $this->storeReceipt($receipt, $data['request_code']) : ['path' => null, 'name' => null, 'mime' => null, 'blob' => null];

        $stmt = $this->db->prepare('INSERT INTO reservas (request_code, full_name, rut, phone, email, people_count, total_amount, status, receipt_path, receipt_name, receipt_mime, receipt_blob) VALUES (:request_code, :full_name, :rut, :phone, :email, :people_count, :total_amount, :status, :receipt_path, :receipt_name, :receipt_mime, :receipt_blob)');
        $stmt->execute([
            'request_code' => $data['request_code'],
            'full_name' => $data['full_name'],
            'rut' => $data['rut'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'people_count' => $data['people_count'],
            'total_amount' => $data['total_amount'],
            'status' => 'pending',
            'receipt_path' => $storedReceipt['path'],
            'receipt_name' => $storedReceipt['name'],
            'receipt_mime' => $storedReceipt['mime'],
            'receipt_blob' => $storedReceipt['blob'],
        ]);
        $data['receipt_path'] = $storedReceipt['path'];

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


    private function ensureTicketColumns(): void
    {
        try {
            $this->db->exec("ALTER TABLE entradas MODIFY status ENUM('issued','entered','exited','void') NOT NULL DEFAULT 'issued'");
        } catch (Throwable) {}

        foreach ([
            'ticket_code' => 'ALTER TABLE entradas ADD COLUMN ticket_code VARCHAR(40) NULL AFTER id',
            'holder_name' => 'ALTER TABLE entradas ADD COLUMN holder_name VARCHAR(160) NULL AFTER reserva_id',
            'issued_at' => 'ALTER TABLE entradas ADD COLUMN issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status',
            'entered_at' => 'ALTER TABLE entradas ADD COLUMN entered_at DATETIME NULL AFTER issued_at',
            'exited_at' => 'ALTER TABLE entradas ADD COLUMN exited_at DATETIME NULL AFTER entered_at',
            'scanned_by' => 'ALTER TABLE entradas ADD COLUMN scanned_by BIGINT UNSIGNED NULL AFTER exited_at',
        ] as $column => $sql) {
            try {
                $this->db->query('SELECT ' . $column . ' FROM entradas LIMIT 1');
            } catch (Throwable) {
                $this->db->exec($sql);
            }
        }
    }

    private function ensureReceiptColumns(): void
    {
        try {
            $this->db->exec("ALTER TABLE reservas MODIFY status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        } catch (Throwable) {}

        foreach ([
            'receipt_name' => 'ALTER TABLE reservas ADD COLUMN receipt_name VARCHAR(190) NULL AFTER receipt_path',
            'receipt_mime' => 'ALTER TABLE reservas ADD COLUMN receipt_mime VARCHAR(120) NULL AFTER receipt_name',
            'receipt_blob' => 'ALTER TABLE reservas ADD COLUMN receipt_blob LONGBLOB NULL AFTER receipt_mime',
        ] as $column => $sql) {
            try {
                $this->db->query('SELECT ' . $column . ' FROM reservas LIMIT 1');
            } catch (Throwable) {
                $this->db->exec($sql);
            }
        }
    }

    private function storeReceipt(array $receipt, string $code): array
    {
        $extension = strtolower(pathinfo((string)$receipt['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($extension, $allowed, true)) throw new InvalidArgumentException('El comprobante debe ser imagen o PDF.');

        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
        $blob = file_get_contents((string)$receipt['tmp_name']);
        if ($blob === false) throw new RuntimeException('No fue posible leer el comprobante.');

        $fileName = $code . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $path = null;
        foreach ([dirname(__DIR__, 2) . '/public/storage/comprobantes', dirname(__DIR__, 2) . '/storage/comprobantes'] as $dir) {
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) continue;
            if (!is_writable($dir)) continue;
            $target = $dir . '/' . $fileName;
            if (@copy((string)$receipt['tmp_name'], $target)) $path = '/storage/comprobantes/' . $fileName;
        }

        return ['path' => $path, 'name' => (string)$receipt['name'], 'mime' => $mime, 'blob' => $blob];
    }
}
