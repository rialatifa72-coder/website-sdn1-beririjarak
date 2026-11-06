-- Jalankan sekali di phpMyAdmin pada database db_sdn1beririjarak_baru.
-- Akun lama Admin2026 diubah menjadi admin. Password awal akan di-hash
-- otomatis oleh login.php ketika admin berhasil login pertama kali.

UPDATE tb_users
SET username = 'admin',
    password = '12345',
    nama_lengkap = 'Administrator Sekolah'
WHERE username = 'Admin2026';

UPDATE tb_users
SET password = '12345',
    nama_lengkap = 'Administrator Sekolah'
WHERE username = 'admin';

INSERT INTO tb_users (username, password, nama_lengkap)
SELECT 'admin', '12345', 'Administrator Sekolah'
WHERE NOT EXISTS (
    SELECT 1 FROM tb_users WHERE username = 'admin'
);
