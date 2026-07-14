-- Usuario administrativo inicial para pruebas locales.
-- Cambiar la contraseña inmediatamente después del primer ingreso.
-- Usuario: adminfiesta
-- Password inicial: Admin$

USE fiesta_ochentera;

INSERT INTO admin_users (name, username, email, password_hash, role, is_active)
VALUES (
  'Administrador Fiesta 80s',
  'adminfiesta',
  'adminfiesta@fiesta80s.local',
  '$2y$12$43esaSM2P94l0Aa8RPhyk.omjZi7ye8kgPO5NBpudb1slJjKXoHzG',
  'admin',
  1
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  username = VALUES(username),
  password_hash = VALUES(password_hash),
  role = VALUES(role),
  is_active = VALUES(is_active),
  failed_login_count = 0,
  locked_until = NULL;

INSERT IGNORE INTO reservas (request_code, full_name, rut, phone, email, people_count, total_amount, status)
VALUES
  ('RSV-80S-001', 'Reserva Demo Pendiente', '11.111.111-1', '+56911111111', 'demo1@fiesta80s.local', 4, 60000, 'pending'),
  ('RSV-80S-002', 'Reserva Demo Confirmada', '22.222.222-2', '+56922222222', 'demo2@fiesta80s.local', 2, 30000, 'approved');

INSERT IGNORE INTO entradas (reserva_id, qr_token_hash, status)
SELECT id, SHA2(CONCAT(request_code, '-ticket-1'), 256), 'issued'
FROM reservas
WHERE request_code IN ('RSV-80S-001', 'RSV-80S-002');

INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES
  ('event_name', 'Fiesta Ochentera Solidaria'),
  ('sales_mode', 'Reservas con confirmación'),
  ('notifications', 'Activas');
