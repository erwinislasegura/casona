-- Migración simple para XAMPP/MariaDB: agrega soporte de usuario corto.
-- Se puede ejecutar más de una vez porque usa IF NOT EXISTS.
USE fiesta_ochentera;

ALTER TABLE admin_users
  ADD COLUMN IF NOT EXISTS username VARCHAR(60) NULL AFTER name;

UPDATE admin_users
SET username = LOWER(SUBSTRING_INDEX(email, '@', 1))
WHERE username IS NULL OR username = '';

ALTER TABLE admin_users
  MODIFY username VARCHAR(60) NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_admin_users_username ON admin_users (username);
