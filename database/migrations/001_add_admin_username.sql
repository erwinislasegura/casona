-- Migración idempotente para agregar usuario corto a instalaciones existentes.
-- Se puede ejecutar más de una vez sin fallar por columna/índice existente.
USE fiesta_ochentera;

DELIMITER $$

DROP PROCEDURE IF EXISTS add_admin_username_support $$
CREATE PROCEDURE add_admin_username_support()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'admin_users'
      AND COLUMN_NAME = 'username'
  ) THEN
    ALTER TABLE admin_users ADD COLUMN username VARCHAR(60) NULL AFTER name;
  END IF;

  UPDATE admin_users
  SET username = LOWER(SUBSTRING_INDEX(email, '@', 1))
  WHERE username IS NULL OR username = '';

  ALTER TABLE admin_users MODIFY username VARCHAR(60) NOT NULL;

  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'admin_users'
      AND INDEX_NAME = 'uq_admin_users_username'
  ) THEN
    CREATE UNIQUE INDEX uq_admin_users_username ON admin_users (username);
  END IF;
END $$

DELIMITER ;

CALL add_admin_username_support();
DROP PROCEDURE IF EXISTS add_admin_username_support;
