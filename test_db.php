<?php
require_once __DIR__ . "/koneksi.php";
header("Content-Type: text/plain; charset=utf-8");

if (!$koneksi) {
    http_response_code(500);
    exit("Koneksi database gagal. Pastikan MySQL aktif dan database db_sdn1beririjarak_baru sudah disiapkan. Detail: " . $koneksi_error);
}

$requiredTables = ["tb_users", "tb_guru", "tb_siswa", "tb_berita", "tb_galeri", "tb_media", "tb_profil"];
$missing = [];
foreach ($requiredTables as $table) {
    $safeTable = mysqli_real_escape_string($koneksi, $table);
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE '" . $safeTable . "'");
    if (!$result || mysqli_num_rows($result) === 0) {
        $missing[] = $table;
    }
}
if ($missing) {
    http_response_code(500);
    exit("Koneksi berhasil, tetapi tabel berikut belum tersedia: " . implode(", ", $missing) . ". Jalankan installer database terlebih dahulu.");
}

$result = mysqli_query($koneksi, "SELECT id FROM tb_users WHERE username = 'admin' LIMIT 1");
if (!$result || mysqli_num_rows($result) === 0) {
    http_response_code(500);
    exit("Tabel tersedia, tetapi akun administrator belum ditemukan. Jalankan installer database.");
}

echo "OK: database db_sdn1beririjarak_baru, tujuh tabel, dan akun administrator tersedia.";
