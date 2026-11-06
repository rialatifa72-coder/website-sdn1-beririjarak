<?php
// Installer aman dan idempoten: membuat database/tabel jika belum ada,
// tidak menghapus atau mengosongkan data yang sudah tersimpan.
mysqli_report(MYSQLI_REPORT_OFF);
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
$conn->query("INSERT INTO tb_users (username,password,nama_lengkap)
 SELECT 'admin','12345','Administrator Sekolah'
 WHERE NOT EXISTS (SELECT 1 FROM tb_users WHERE username='admin')");
$conn->query("INSERT INTO tb_profil (nama_sekolah)
 SELECT 'SDN 1 Beririjarak' WHERE NOT EXISTS (SELECT 1 FROM tb_profil)");
$conn->close();
?>
<!doctype html>
<html lang="id"><meta charset="utf-8"><title>Setup Database SDN 1 Beririjarak</title>
<body style="font-family:Arial,sans-serif;max-width:700px;margin:50px auto;padding:20px">
<h2>Database berhasil disiapkan</h2>
<p>Database <b>db_sdn1beririjarak_baru</b> dan tabel pendukung telah dibuat atau diperiksa.</p>
<p>Akun awal: <b>admin</b> / <b>12345</b></p>
<p>Silakan buka <a href="login.php">halaman login administrator</a>.</p>
<p>Untuk keamanan, hapus file setup_database.php dari repositori setelah instalasi selesai.</p>
</body></html>
