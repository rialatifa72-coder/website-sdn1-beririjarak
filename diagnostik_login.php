<?php
require_once __DIR__ . "/koneksi.php";
header("Content-Type: text/plain; charset=utf-8");

if (!$koneksi) {
    exit("HASIL: KONEKSI_GAGAL\nDatabase yang dikonfigurasi: db_sdn1beririjarak_baru\nDetail: " . ($koneksi_error ?? "Tidak diketahui"));
}

$dbResult = mysqli_query($koneksi, "SELECT DATABASE() AS db");
$dbRow = $dbResult ? mysqli_fetch_assoc($dbResult) : [];
echo "DATABASE_AKTIF: " . ($dbRow["db"] ?? "tidak diketahui") . "\n";

$columns = [];
$colResult = mysqli_query($koneksi, "SHOW COLUMNS FROM tb_users");
if (!$colResult) {
    exit("TABEL tb_users tidak dapat dibaca: " . mysqli_error($koneksi));
}
while ($col = mysqli_fetch_assoc($colResult)) {
    $columns[] = $col["Field"];
}
echo "KOLOM: " . implode(", ", $columns) . "\n";

if (!in_array("username", $columns, true) || !in_array("password", $columns, true)) {
    exit("STRUKTUR_KOLOM_TIDAK_SESUAI\n");
}

$stmt = mysqli_prepare($koneksi, "SELECT username, password FROM tb_users WHERE username = ? LIMIT 1");
if (!$stmt) {
    exit("QUERY_GAGAL: " . mysqli_error($koneksi));
}
$username = "admin";
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $dbUsername, $dbPassword);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$found) {
    echo "AKUN_ADMIN: TIDAK_DITEMUKAN\n";
    exit;
}
$stored = (string)$dbPassword;
echo "AKUN_ADMIN: DITEMUKAN\n";
echo "PASSWORD_FORMAT: " . (password_get_info($stored)["algo"] ? "HASH" : "TEKS_BIASA") . "\n";
echo "PASSWORD_12345_COCOK: " . ((password_verify("12345", $stored) || hash_equals($stored, "12345")) ? "YA" : "TIDAK") . "\n";
echo "FILE_KONEKSI: " . realpath(__DIR__ . "/koneksi.php") . "\n";
echo "FILE_LOGIN: " . realpath(__DIR__ . "/login.php") . "\n";
