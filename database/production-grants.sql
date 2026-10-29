-- Run as a database administrator, after creating the application database.
-- Replace the password with a unique secret stored only in the hosting environment.
CREATE USER IF NOT EXISTS 'app_user'@'localhost' IDENTIFIED BY 'replace-with-a-long-random-secret';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE
ON `krus_na_ligas_db`.* TO 'app_user'@'localhost';
FLUSH PRIVILEGES;
