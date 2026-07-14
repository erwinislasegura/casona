-- Usuario administrativo inicial para pruebas locales.
-- Cambiar la contraseña inmediatamente después del primer ingreso.
-- Email: admin@fiesta80s.cl
-- Password inicial: Admin12345!

USE fiesta_ochentera;

INSERT INTO admin_users (name, email, password_hash, role, is_active)
VALUES (
  'Administrador Fiesta 80s',
  'admin@fiesta80s.cl',
  '$2y$12$JwF4Y0i2nlpN4iHobomqTO.teNEH/E1pLiJbz2R7fMZ5wEe94upYO',
  'admin',
  1
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  role = VALUES(role),
  is_active = VALUES(is_active);
