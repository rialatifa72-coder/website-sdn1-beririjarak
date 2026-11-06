<?php
require_once __DIR__ . "/koneksi.php";
header("Content-Type: text/plain; charset=utf-8");

if (!$koneksi) {
    http_response_code(500);
    exit("Koneksi gagal: " . $koneksi_error);
}

$tables = ["tb_users", "tb_guru", "tb_siswa", "tb_berita", "tb_galeri", "tb_media", "tb_profil", "tb_pendaftaran"];
$missing = [];

foreach ($tables as $table) {
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE '" . mysqli_real_escape_string($koneksi, $table) . "'");
    if (!$result || mysqli_num_rows($result) !== 1) {
        $missing[] = $table;
    }
}

if ($missing) {
    http_response_code(500);
    exit("Tabel belum lengkap: " . implode(", ", $missing) . ". Periksa dan jalankan database.sql setelah mencadangkan database.");
}

$stmt = mysqli_prepare($koneksi, "SELECT password FROM tb_users WHERE username = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    exit("Pemeriksaan akun administrator gagal: " . mysqli_error($koneksi));
}
$username = "Admin2026";
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $storedPassword);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$found) {
    http_response_code(500);
    exit("Akun Admin2026 belum tersedia. Periksa apakah bagian INSERT akun pada database.sql sudah dijalankan.");
}

$storedPassword = (string) $storedPassword;
$hashValid = password_verify("AdminIPA", $storedPassword);
$plainValid = hash_equals("AdminIPA", $storedPassword);

if (!$hashValid && !$plainValid) {
    http_response_code(500);
    exit("Akun Admin2026 ditemukan, tetapi password tersimpan tidak cocok. Periksa kembali data akun pada tabel tb_users.");
}

if ($plainValid) {
    echo "Database dan akun Admin2026 ditemukan. Password masih dalam format awal; silakan login satu kali dengan password AdminIPA. Sistem login akan mengubahnya menjadi hash secara otomatis.";
} else {
    echo "BERHASIL: database db_sdn1beririjarak_baru terhubung, 8 tabel tersedia, dan akun Admin2026 / AdminIPA terverifikasi dengan password hash.";
}
