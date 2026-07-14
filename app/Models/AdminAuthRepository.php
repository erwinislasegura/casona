<?php

final class AdminAuthRepository
{
    private const SEEDED_USERNAME = 'adminfiesta';
    private const SEEDED_EMAIL = 'adminfiesta@fiesta80s.local';
    private const SEEDED_PASSWORD_HASH = '$2y$12$43esaSM2P94l0Aa8RPhyk.omjZi7ye8kgPO5NBpudb1slJjKXoHzG';

    public function __construct(private PDO $db) {}

    public function findActiveUserByEmail(string $email): ?array
    {
        $login = mb_strtolower(trim($email));
        $this->ensureAuthTables();
        $this->ensureUsernameSupport();
        $this->createSeedAdminIfNeeded($login);

        $stmt = $this->db->prepare('SELECT * FROM admin_users WHERE (email = :email_login OR username = :username_login) AND is_active = 1 LIMIT 1');
        $stmt->execute([
            'email_login' => $login,
            'username_login' => $login,
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function recordLoginAttempt(string $email, ?int $userId, string $ip, string $userAgent, bool $success, ?string $reason = null): void
    {
        $stmt = $this->db->prepare('INSERT INTO admin_login_attempts (email, user_id, ip_address, user_agent, was_successful, failure_reason) VALUES (:email, :user_id, INET6_ATON(:ip), :user_agent, :success, :reason)');
        $stmt->execute([
            'email' => mb_strtolower(trim($email)),
            'user_id' => $userId,
            'ip' => $ip,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'success' => $success ? 1 : 0,
            'reason' => $reason,
        ]);
    }

    public function markSuccessfulLogin(int $userId, string $ip): void
    {
        $stmt = $this->db->prepare('UPDATE admin_users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW(), last_login_ip = INET6_ATON(:ip) WHERE id = :id');
        $stmt->execute(['id' => $userId, 'ip' => $ip]);
    }

    public function registerFailedLogin(int $userId, int $maxAttempts = 5, int $lockMinutes = 15): void
    {
        $stmt = $this->db->prepare('UPDATE admin_users SET failed_login_count = failed_login_count + 1, locked_until = CASE WHEN failed_login_count + 1 >= :max_attempts THEN DATE_ADD(NOW(), INTERVAL :lock_minutes MINUTE) ELSE locked_until END WHERE id = :id');
        $stmt->execute(['id' => $userId, 'max_attempts' => $maxAttempts, 'lock_minutes' => $lockMinutes]);
    }

    public function storeRememberToken(int $userId, string $selector, string $plainToken, DateTimeInterface $expiresAt): void
    {
        $stmt = $this->db->prepare('INSERT INTO admin_remember_tokens (user_id, selector, token_hash, expires_at) VALUES (:user_id, :selector, :token_hash, :expires_at)');
        $stmt->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    private function ensureUsernameSupport(): void
    {
        try {
            $this->db->query('SELECT username FROM admin_users LIMIT 1');
            return;
        } catch (PDOException) {
            // Existing local databases created before username support need a one-time repair.
        }

        try {
            $this->db->exec('ALTER TABLE admin_users ADD COLUMN username VARCHAR(60) NULL AFTER name');
        } catch (PDOException) {
            // Column may already exist after a concurrent/manual migration.
        }

        $this->db->exec("UPDATE admin_users SET username = LOWER(SUBSTRING_INDEX(email, '@', 1)) WHERE username IS NULL OR username = ''");

        try {
            $this->db->exec('ALTER TABLE admin_users MODIFY username VARCHAR(60) NOT NULL');
        } catch (PDOException) {
            // Keep login usable even if the local MySQL variant cannot modify the column inline.
        }

        try {
            $this->db->exec('CREATE UNIQUE INDEX uq_admin_users_username ON admin_users (username)');
        } catch (PDOException) {
            // Index may already exist.
        }
    }

    private function ensureAuthTables(): void
    {
        $this->db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS admin_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','scanner','viewer') NOT NULL DEFAULT 'viewer',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  last_login_ip VARBINARY(16) NULL,
  failed_login_count INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admin_users_username (username),
  UNIQUE KEY uq_admin_users_email (email),
  KEY idx_admin_users_active (is_active),
  KEY idx_admin_users_locked_until (locked_until)
) ENGINE=InnoDB
SQL);

        $this->db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS admin_login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARBINARY(16) NOT NULL,
  user_agent VARCHAR(255) NULL,
  was_successful TINYINT(1) NOT NULL DEFAULT 0,
  failure_reason VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_login_attempts_email_created (email, created_at),
  KEY idx_login_attempts_ip_created (ip_address, created_at),
  CONSTRAINT fk_login_attempts_user FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB
SQL);
    }

    private function createSeedAdminIfNeeded(string $login): void
    {
        if (!in_array($login, [self::SEEDED_USERNAME, self::SEEDED_EMAIL], true)) {
            return;
        }

        $stmt = $this->db->prepare('INSERT INTO admin_users (name, username, email, password_hash, role, is_active, failed_login_count, locked_until) VALUES (:name, :username, :email, :password_hash, :role, 1, 0, NULL) ON DUPLICATE KEY UPDATE username = VALUES(username), password_hash = VALUES(password_hash), role = VALUES(role), is_active = 1, failed_login_count = 0, locked_until = NULL');
        $stmt->execute([
            'name' => 'Administrador Fiesta 80s',
            'username' => self::SEEDED_USERNAME,
            'email' => self::SEEDED_EMAIL,
            'password_hash' => self::SEEDED_PASSWORD_HASH,
            'role' => 'admin',
        ]);
    }
}
