<?php
require_once __DIR__ . "/koneksi.php";
header("Content-Type: text/plain; charset=utf-8");

if (!$koneksi) {
    http_response_code(500);
    exit("Koneksi database gagal. Pastikan MySQL aktif dan database db_sdn1beririjarak sudah dibuat. Detail: " . $koneksi_error);
}

$result = mysqli_query($koneksi, "SHOW TABLES LIKE 'tb_users'");
if (!$result || mysqli_num_rows($result) === 0) {
    http_response_code(500);
    exit("Koneksi berhasil, tetapi tabel tb_users belum tersedia. Impor database.sql melalui phpMyAdmin.");
}

echo "OK: koneksi database dan tabel tb_users tersedia.";
