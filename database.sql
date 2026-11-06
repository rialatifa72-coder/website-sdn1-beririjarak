-- Database website SDN 1 Beririjarak
-- Import file ini melalui phpMyAdmin (tab Import).
CREATE DATABASE IF NOT EXISTS db_sdn1beririjarak
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_sdn1beririjarak;

CREATE TABLE IF NOT EXISTS tb_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Akun awal: username admin, password 12345.
-- Setelah login pertama, aplikasi otomatis mengubah password teks biasa menjadi password hash.
INSERT INTO tb_users (username, password)
SELECT 'admin', '12345'
WHERE NOT EXISTS (SELECT 1 FROM tb_users WHERE username = 'admin');
