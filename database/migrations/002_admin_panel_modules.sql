-- Soporte para módulos funcionales del panel administrativo.
USE fiesta_ochentera;

CREATE TABLE IF NOT EXISTS admin_settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES
  ('event_name', 'Fiesta Ochentera Solidaria'),
  ('sales_mode', 'Reservas con confirmación'),
  ('notifications', 'Activas');
