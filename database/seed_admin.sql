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
