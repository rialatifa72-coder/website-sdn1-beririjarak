-- Tambahkan kolom NIPD agar CRUD dan impor siswa dapat menyimpan Nomor Induk Peserta Didik.
-- Jalankan SATU KALI melalui phpMyAdmin pada database db_sdn1beririjarak_baru.
USE db_sdn1beririjarak_baru;
ALTER TABLE tb_siswa ADD COLUMN nipd VARCHAR(30) DEFAULT NULL AFTER nis;
