<?php
// Migrasi akun administrator satu kali, dibatasi untuk localhost.
mysqli_report(MYSQLI_REPORT_OFF);
if (!in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true)) {
    http_response_code(403);
    exit("Akses ditolak. Jalankan halaman ini melalui localhost.");
}
require_once __DIR__ . "/koneksi.php";
if (!$koneksi) {
    http_response_code(500);
    exit("Koneksi ke database baru gagal: " . htmlspecialchars($koneksi_error, ENT_QUOTES, "UTF-8"));
}

$cekTabel = mysqli_query($koneksi, "SHOW TABLES LIKE 'tb_users'");
if (!$cekTabel || mysqli_num_rows($cekTabel) !== 1) {
    http_response_code(500);
    exit("Tabel tb_users belum tersedia. Jalankan setup database terlebih dahulu.");
}

$hash = password_hash("AdminIPA", PASSWORD_DEFAULT);
$cekAdmin = mysqli_prepare($koneksi, "SELECT id FROM tb_users WHERE username = 'Admin2026' LIMIT 1");
if (!$cekAdmin || !mysqli_stmt_execute($cekAdmin)) {
    http_response_code(500);
    exit("Pemeriksaan akun administrator gagal.");
}
mysqli_stmt_store_result($cekAdmin);
$adminAda = mysqli_stmt_num_rows($cekAdmin) > 0;
mysqli_stmt_close($cekAdmin);

if ($adminAda) {
    $stmt = mysqli_prepare($koneksi, "UPDATE tb_users SET password = ?, nama_lengkap = 'Administrator Sekolah' WHERE username = 'Admin2026'");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $hash);
    }
} else {
    // Jika akun lama bernama 'admin' ada, ubah namanya; jika tidak, buat akun baru.
    $cekLama = mysqli_prepare($koneksi, "SELECT id FROM tb_users WHERE username = 'admin' LIMIT 1");
    if (!$cekLama || !mysqli_stmt_execute($cekLama)) {
        http_response_code(500);
        exit("Pemeriksaan akun lama gagal.");
    }
    mysqli_stmt_store_result($cekLama);
    $akunLamaAda = mysqli_stmt_num_rows($cekLama) > 0;
    mysqli_stmt_close($cekLama);

    if ($akunLamaAda) {
        $stmt = mysqli_prepare($koneksi, "UPDATE tb_users SET username = 'Admin2026', password = ?, nama_lengkap = 'Administrator Sekolah' WHERE username = 'admin'");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $hash);
        }
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_users (username, password, nama_lengkap) VALUES ('Admin2026', ?, 'Administrator Sekolah')");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $hash);
        }
    }
}

if (!$stmt || !mysqli_stmt_execute($stmt)) {
    $detail = $stmt ? mysqli_stmt_error($stmt) : mysqli_error($koneksi);
    http_response_code(500);
    exit("Migrasi akun gagal: " . htmlspecialchars($detail, ENT_QUOTES, "UTF-8"));
}
mysqli_stmt_close($stmt);

$verifikasi = mysqli_prepare($koneksi, "SELECT id FROM tb_users WHERE username = 'Admin2026' LIMIT 1");
mysqli_stmt_execute($verifikasi);
mysqli_stmt_store_result($verifikasi);
$berhasil = mysqli_stmt_num_rows($verifikasi) === 1;
mysqli_stmt_close($verifikasi);

if (!$berhasil) {
    http_response_code(500);
    exit("Akun belum berhasil diverifikasi. Jangan hapus database lama.");
}
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Migrasi Admin</title></head>
<body style="font-family:Arial,sans-serif;max-width:650px;margin:50px auto;padding:20px;line-height:1.6">
<h2>Akun administrator berhasil disiapkan</h2>
<p>Username: <b>Admin2026</b><br>Password: <b>AdminIPA</b></p>
<p><a href="test_db.php">Periksa database</a> · <a href="login.php">Buka halaman login</a></p>
<p>Setelah berhasil, hapus file <b>migrasi_admin.php</b> dari folder website untuk keamanan.</p>
</body></html>