-- Fiesta Ochentera Solidaria - esquema base MySQL 8+
-- Ejecutar con: mysql -u USER -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS fiesta_ochentera
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fiesta_ochentera;

CREATE TABLE IF NOT EXISTS admin_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
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
  UNIQUE KEY uq_admin_users_email (email),
  KEY idx_admin_users_active (is_active),
  KEY idx_admin_users_locked_until (locked_until)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_remember_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  selector CHAR(24) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at DATETIME NULL,
  UNIQUE KEY uq_remember_selector (selector),
  KEY idx_remember_user (user_id),
  KEY idx_remember_expiry (expires_at, revoked_at),
  CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_password_resets_user (user_id),
  KEY idx_password_resets_expiry (expires_at, used_at),
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_sessions (
  id CHAR(128) PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  ip_address VARBINARY(16) NULL,
  user_agent VARCHAR(255) NULL,
  last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sessions_user (user_id),
  KEY idx_sessions_expiry (expires_at, revoked_at),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservas (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entradas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_id BIGINT UNSIGNED NOT NULL,
  qr_token_hash CHAR(64) NOT NULL,
  status ENUM('issued','entered','exited','void') NOT NULL DEFAULT 'issued',
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  entered_at DATETIME NULL,
  exited_at DATETIME NULL,
  scanned_by BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_entradas_qr_token_hash (qr_token_hash),
  KEY idx_entradas_status (status),
  CONSTRAINT fk_entradas_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
  CONSTRAINT fk_entradas_scanner FOREIGN KEY (scanned_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
