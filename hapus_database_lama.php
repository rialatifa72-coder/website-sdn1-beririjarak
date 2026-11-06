<?php
// Penghapus database lama khusus localhost.
// Database baru WAJIB lolos verifikasi sebelum database lama dapat dihapus.
mysqli_report(MYSQLI_REPORT_OFF);

if (!in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true)) {
    http_response_code(403);
    exit("Akses ditolak. Halaman ini hanya dapat digunakan dari localhost.");
}

$host = "localhost";
$user = "root";
$password = "";
$databaseBaru = "db_sdn1beririjarak_baru";
$databaseLama = "db_sdn1beririjarak";

$conn = @new mysqli($host, $user, $password);
if ($conn->connect_error) {
    http_response_code(500);
    exit("Koneksi MySQL gagal: " . htmlspecialchars($conn->connect_error, ENT_QUOTES, "UTF-8"));
}
$conn->set_charset("utf8mb4");

$pesan = "";
$siap = false;
$cekDb = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . $conn->real_escape_string($databaseBaru) . "'");
if (!$cekDb || $cekDb->num_rows !== 1) {
    $pesan = "Database baru belum ditemukan. Database lama tidak dihapus. Jalankan setup_database.php dan pastikan database baru sudah tersedia.";
} else {
    $conn->select_db($databaseBaru);
    $tabelWajib = ["tb_users", "tb_guru", "tb_siswa", "tb_berita", "tb_galeri", "tb_media", "tb_profil"];
    $tabelAda = true;
    foreach ($tabelWajib as $tabel) {
        $cek = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tabel) . "'");
        if (!$cek || $cek->num_rows !== 1) {
            $tabelAda = false;
            break;
        }
    }
    $akun = $conn->query("SELECT id FROM tb_users WHERE username = 'Admin2026' LIMIT 1");
    if (!$tabelAda || !$akun || $akun->num_rows !== 1) {
        $pesan = "Verifikasi database baru gagal: tujuh tabel dan akun Admin2026 harus tersedia. Database lama tetap aman dan tidak dihapus.";
    } else {
        $siap = true;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $siap) {
    $konfirmasi = trim($_POST["konfirmasi"] ?? "");
    if (!hash_equals("HAPUS DATABASE LAMA", $konfirmasi)) {
        $pesan = "Teks konfirmasi tidak cocok. Database lama belum dihapus.";
    } else {
        // Nama database dibatasi pada konstanta di atas, bukan input pengguna.
        $hasil = $conn->query("DROP DATABASE IF EXISTS `db_sdn1beririjarak`");
        if ($hasil) {
            $pesan = "BERHASIL: database lama db_sdn1beririjarak telah dihapus. Database baru db_sdn1beririjarak_baru tetap dipertahankan.";
            $siap = false;
        } else {
            $pesan = "Penghapusan gagal: " . $conn->error;
        }
    }
}
$conn->close();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hapus Database Lama - SDN 1 Beririjarak</title>
<style>
body{font-family:Arial,sans-serif;background:#f3f6fb;margin:0;padding:24px;color:#243247}
main{max-width:680px;margin:40px auto;background:#fff;padding:28px;border-radius:14px;box-shadow:0 8px 30px #172b4d18}
h2{margin-top:0;color:#123b70}.notice{padding:14px;border-radius:8px;background:#eef5ff;line-height:1.6}
.warning{background:#fff3cd;color:#664d03}.result{margin:16px 0;padding:14px;background:#e8f5e9;border-radius:8px;line-height:1.6}
label{display:block;margin:18px 0 8px;font-weight:bold}input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #b8c4d3;border-radius:7px;font-size:16px}
button{margin-top:14px;background:#b42318;color:#fff;border:0;border-radius:7px;padding:12px 18px;font-size:16px;font-weight:bold;cursor:pointer}
a{color:#1459a6}
</style>
</head>
<body><main>
<h2>Penghapusan Database Lama</h2>
<p class="notice">Database baru: <b>db_sdn1beririjarak_baru</b><br>Database yang akan dihapus: <b>db_sdn1beririjarak</b></p>
<?php if ($pesan !== ""): ?>
<div class="result"><?php echo htmlspecialchars($pesan, ENT_QUOTES, "UTF-8"); ?></div>
<?php endif; ?>
<?php if ($siap): ?>
<div class="notice warning"><b>Perhatian:</b> tindakan ini menghapus seluruh tabel dan data di database lama secara permanen. Sistem sudah memverifikasi bahwa database baru memiliki tujuh tabel dan akun Admin2026.</div>
<form method="post" onsubmit="return confirm('Yakin menghapus seluruh database lama db_sdn1beririjarak?');">
<label for="konfirmasi">Ketik persis: HAPUS DATABASE LAMA</label>
<input id="konfirmasi" name="konfirmasi" required autocomplete="off" placeholder="HAPUS DATABASE LAMA">
<button type="submit">Hapus Database Lama</button>
</form>
<?php endif; ?>
<p><a href="test_db.php">Periksa database baru</a> · <a href="login.php">Kembali ke login</a></p>
</main></body></html>
