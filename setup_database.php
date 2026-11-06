<?php
// Installer satu kali untuk database SDN 1 Beririjarak.
// Membuat database/tabel tanpa menghapus tabel atau data lain.
mysqli_report(MYSQLI_REPORT_OFF);

if (!in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true)) {
    http_response_code(403);
    exit("Installer hanya boleh dijalankan dari komputer lokal (localhost).");
}

$lockFile = __DIR__ . DIRECTORY_SEPARATOR . ".database_setup_complete";
if (is_file($lockFile)) {
    exit("Installer sudah pernah dijalankan. Untuk keamanan, hapus file setup_database.php dari folder website. Jika perlu memperbaiki database, gunakan database.sql setelah membuat cadangan.");
}

$host = "localhost";
$user = "root";
$password = "";
$db = "db_sdn1beririjarak_baru";

$conn = @new mysqli($host, $user, $password);
if ($conn->connect_error) {
    http_response_code(500);
    exit("Koneksi MySQL gagal: " . htmlspecialchars($conn->connect_error, ENT_QUOTES, "UTF-8"));
}
$conn->set_charset("utf8mb4");

if (!$conn->query("CREATE DATABASE IF NOT EXISTS `" . $db . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    http_response_code(500);
    exit("Database tidak dapat dibuat: " . htmlspecialchars($conn->error, ENT_QUOTES, "UTF-8"));
}
$conn->select_db($db);

$queries = [
"CREATE TABLE IF NOT EXISTS tb_users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 nama_lengkap VARCHAR(100) DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS tb_guru (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nip VARCHAR(30) DEFAULT NULL,
 nama_guru VARCHAR(150) NOT NULL, jenis_kelamin ENUM('Laki-laki','Perempuan') DEFAULT NULL,
 jabatan VARCHAR(100) DEFAULT NULL, pendidikan VARCHAR(100) DEFAULT NULL,
 foto VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS tb_siswa (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nis VARCHAR(30) DEFAULT NULL,
 nisn VARCHAR(30) DEFAULT NULL, nama_siswa VARCHAR(150) NOT NULL,
 jenis_kelamin ENUM('Laki-laki','Perempuan') DEFAULT NULL,
 tempat_lahir VARCHAR(100) DEFAULT NULL, tanggal_lahir DATE DEFAULT NULL,
 alamat TEXT DEFAULT NULL, kelas VARCHAR(30) DEFAULT NULL, tahun_ajaran VARCHAR(20) DEFAULT NULL,
 status ENUM('Aktif','Lulus','Pindah') DEFAULT 'Aktif',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS tb_berita (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(200) NOT NULL,
 slug VARCHAR(220) DEFAULT NULL, isi LONGTEXT NOT NULL, gambar VARCHAR(255) DEFAULT NULL,
 tanggal_publikasi DATETIME DEFAULT CURRENT_TIMESTAMP,
 status ENUM('Draft','Terbit') DEFAULT 'Draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS tb_galeri (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(150) NOT NULL,
 deskripsi TEXT DEFAULT NULL, nama_file VARCHAR(255) NOT NULL,
 tanggal_kegiatan DATE DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS tb_media (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(200) NOT NULL,
 deskripsi TEXT DEFAULT NULL, mata_pelajaran VARCHAR(100) DEFAULT NULL,
 kelas VARCHAR(30) DEFAULT NULL,
 jenis_media ENUM('Video','Dokumen','Link','Gambar','Lainnya') DEFAULT 'Lainnya',
 file_media VARCHAR(255) DEFAULT NULL, link_media VARCHAR(500) DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS tb_profil (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama_sekolah VARCHAR(150) NOT NULL,
 npsn VARCHAR(30) DEFAULT NULL, alamat TEXT DEFAULT NULL, telepon VARCHAR(30) DEFAULT NULL,
 email VARCHAR(100) DEFAULT NULL, visi TEXT DEFAULT NULL, misi LONGTEXT DEFAULT NULL,
 sambutan_kepala_sekolah LONGTEXT DEFAULT NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($queries as $q) {
    if (!$conn->query($q)) {
        http_response_code(500);
        exit("Pembuatan tabel gagal: " . htmlspecialchars($conn->error, ENT_QUOTES, "UTF-8"));
    }
}

// Reset akun admin ke kredensial awal hanya pada proses instalasi pertama.
$adminHash = password_hash("AdminIPA", PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO tb_users (username, password, nama_lengkap)
 VALUES ('Admin2026', ?, 'Administrator Sekolah')
 ON DUPLICATE KEY UPDATE password = VALUES(password), nama_lengkap = VALUES(nama_lengkap)");
if (!$stmt) {
    http_response_code(500);
    exit("Akun administrator gagal disiapkan: " . htmlspecialchars($conn->error, ENT_QUOTES, "UTF-8"));
}
$stmt->bind_param("s", $adminHash);
if (!$stmt->execute()) {
    http_response_code(500);
    exit("Akun administrator gagal disimpan: " . htmlspecialchars($stmt->error, ENT_QUOTES, "UTF-8"));
}
$stmt->close();

if (!$conn->query("INSERT INTO tb_profil (nama_sekolah)
 SELECT 'SDN 1 Beririjarak' WHERE NOT EXISTS (SELECT 1 FROM tb_profil)")) {
    http_response_code(500);
    exit("Profil sekolah gagal disiapkan: " . htmlspecialchars($conn->error, ENT_QUOTES, "UTF-8"));
}

$tables = ["tb_users", "tb_guru", "tb_siswa", "tb_berita", "tb_galeri", "tb_media", "tb_profil"];
foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    if (!$check || $check->num_rows !== 1) {
        http_response_code(500);
        exit("Verifikasi gagal: tabel " . htmlspecialchars($table, ENT_QUOTES, "UTF-8") . " belum tersedia.");
    }
}

$conn->close();
if (file_put_contents($lockFile, "Database setup completed at " . date("c")) === false) {
    http_response_code(500);
    exit("Database sudah dibuat, tetapi penanda instalasi gagal disimpan. Hapus file installer setelah memastikan database tersedia.");
}
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup Database SDN 1 Beririjarak</title></head>
<body style="font-family:Arial,sans-serif;max-width:700px;margin:50px auto;padding:20px;line-height:1.6">
<h2>Database berhasil disiapkan</h2>
<p>Database <b>db_sdn1beririjarak_baru</b> dan tujuh tabel pendukung telah dibuat serta diverifikasi.</p>
<p>Akun administrator awal: <b>Admin2026</b> / <b>AdminIPA</b></p>
<p><a href="test_db.php">Jalankan pemeriksaan database</a> · <a href="login.php">Buka halaman login</a></p>
<p>Untuk keamanan, installer ini hanya berjalan satu kali. Hapus file setup_database.php dari repositori setelah instalasi selesai.</p>
</body></html>