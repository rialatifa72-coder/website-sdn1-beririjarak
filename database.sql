CREATE DATABASE IF NOT EXISTS db_sdn1beririjarak
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_sdn1beririjarak;

CREATE TABLE IF NOT EXISTS tb_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Buat hash password menggunakan PHP password_hash() sebelum menyimpan.
-- Contoh akun awal: username admin, password admin123
-- Hash di bawah adalah untuk password admin123.
INSERT INTO tb_users (username, password)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.')
ON DUPLICATE KEY UPDATE username = VALUES(username);
