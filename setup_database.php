<?php
// Setup database bersih SDN 1 Beririjarak. Hanya dapat dijalankan dari localhost.
mysqli_report(MYSQLI_REPORT_OFF);
if (!in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true)) {
 http_response_code(403); exit("Akses hanya diizinkan dari localhost.");
}
$host="localhost"; $user="root"; $password=""; $db="db_sdn1beririjarak_baru";
$conn=@new mysqli($host,$user,$password);
if ($conn->connect_error) { http_response_code(500); exit("MySQL gagal: ".htmlspecialchars($conn->connect_error,ENT_QUOTES,"UTF-8")); }
$conn->set_charset("utf8mb4");
if (!$conn->query("CREATE DATABASE IF NOT EXISTS `".$db."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
 http_response_code(500); exit("Database gagal dibuat: ".htmlspecialchars($conn->error,ENT_QUOTES,"UTF-8"));
}
$conn->select_db($db);
$queries=["CREATE TABLE IF NOT EXISTS tb_users (\n id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n username VARCHAR(50) NOT NULL UNIQUE,\n password VARCHAR(255) NOT NULL,\n nama_lengkap VARCHAR(100) DEFAULT NULL,\n created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci","CREATE TABLE IF NOT EXISTS tb_guru (\n id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nip VARCHAR(30) DEFAULT NULL,\n nama_guru VARCHAR(150) NOT NULL, jenis_kelamin ENUM('Laki-laki','Perempuan') DEFAULT NULL,\n jabatan VARCHAR(100) DEFAULT NULL, pendidikan VARCHAR(100) DEFAULT NULL,\n foto VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci","CREATE TABLE IF NOT EXISTS tb_siswa (\n id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nis VARCHAR(30) DEFAULT NULL,\n nisn VARCHAR(30) DEFAULT NULL, nama_siswa VARCHAR(150) NOT NULL,\n jenis_kelamin ENUM('Laki-laki','Perempuan') DEFAULT NULL,\n tempat_lahir VARCHAR(100) DEFAULT NULL, tanggal_lahir DATE DEFAULT NULL,\n alamat TEXT DEFAULT NULL, kelas VARCHAR(30) DEFAULT NULL, tahun_ajaran VARCHAR(20) DEFAULT NULL,\n status ENUM('Aktif','Lulus','Pindah') DEFAULT 'Aktif',\n created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci","CREATE TABLE IF NOT EXISTS tb_berita (\n id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(200) NOT NULL,\n slug VARCHAR(220) DEFAULT NULL, isi LONGTEXT NOT NULL, gambar VARCHAR(255) DEFAULT NULL,\n tanggal_publikasi DATETIME DEFAULT CURRENT_TIMESTAMP,\n status ENUM('Draft','Terbit') DEFAULT 'Draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci","CREATE TABLE IF NOT EXISTS tb_galeri (\n id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(150) NOT NULL,\n deskripsi TEXT DEFAULT NULL, nama_file VARCHAR(255) NOT NULL,\n tanggal_kegiatan DATE DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci","CREATE TABLE IF NOT EXISTS tb_media (\n id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(200) NOT NULL,\n deskripsi TEXT DEFAULT NULL, mata_pelajaran VARCHAR(100) DEFAULT NULL,\n kelas VARCHAR(30) DEFAULT NULL,\n jenis_media ENUM('Video','Dokumen','Link','Gambar','Lainnya') DEFAULT 'Lainnya',\n file_media VARCHAR(255) DEFAULT NULL, link_media VARCHAR(500) DEFAULT NULL,\n created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci","CREATE TABLE IF NOT EXISTS tb_profil (\n id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama_sekolah VARCHAR(150) NOT NULL,\n npsn VARCHAR(30) DEFAULT NULL, alamat TEXT DEFAULT NULL, telepon VARCHAR(30) DEFAULT NULL,\n email VARCHAR(100) DEFAULT NULL, visi TEXT DEFAULT NULL, misi LONGTEXT DEFAULT NULL,\n sambutan_kepala_sekolah LONGTEXT DEFAULT NULL,\n updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci","CREATE TABLE IF NOT EXISTS tb_pendaftaran (\n id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n nomor_pendaftaran VARCHAR(40) NOT NULL UNIQUE,\n nama_lengkap VARCHAR(150) NOT NULL,\n jenis_kelamin ENUM('Laki-laki','Perempuan') DEFAULT NULL,\n tempat_lahir VARCHAR(100) DEFAULT NULL, tanggal_lahir DATE DEFAULT NULL,\n alamat TEXT DEFAULT NULL, nama_orang_tua VARCHAR(150) DEFAULT NULL,\n no_hp VARCHAR(30) DEFAULT NULL, asal_tk VARCHAR(150) DEFAULT NULL,\n tahun_ajaran VARCHAR(20) DEFAULT NULL,\n status ENUM('Menunggu','Diterima','Ditolak') DEFAULT 'Menunggu',\n created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"];
foreach ($queries as $sql) {
 if (!$conn->query($sql)) { http_response_code(500); exit("Pembuatan tabel gagal: ".htmlspecialchars($conn->error,ENT_QUOTES,"UTF-8")); }
}
$hash=password_hash("AdminIPA",PASSWORD_DEFAULT);
$stmt=$conn->prepare("INSERT INTO tb_users (username,password,nama_lengkap) VALUES ('Admin2026',?,'Administrator Sekolah') ON DUPLICATE KEY UPDATE password=VALUES(password),nama_lengkap=VALUES(nama_lengkap)");
if (!$stmt) { http_response_code(500); exit("Persiapan akun gagal: ".htmlspecialchars($conn->error,ENT_QUOTES,"UTF-8")); }
$stmt->bind_param("s",$hash);
if (!$stmt->execute()) { http_response_code(500); exit("Penyimpanan akun gagal: ".htmlspecialchars($stmt->error,ENT_QUOTES,"UTF-8")); }
$stmt->close();
$conn->query("INSERT INTO tb_profil (nama_sekolah) SELECT 'SDN 1 Beririjarak' WHERE NOT EXISTS (SELECT 1 FROM tb_profil)");
$tables=["tb_users","tb_guru","tb_siswa","tb_berita","tb_galeri","tb_media","tb_profil","tb_pendaftaran"];
$missing=[];
foreach($tables as $table) { $res=$conn->query("SHOW TABLES LIKE '".$conn->real_escape_string($table)."'"); if(!$res || $res->num_rows!==1) $missing[]=$table; }
$verify=$conn->prepare("SELECT password FROM tb_users WHERE username='Admin2026' LIMIT 1");
$verify->execute(); $verify->bind_result($stored); $exists=$verify->fetch(); $verify->close();
$accountOk=$exists && password_verify("AdminIPA",$stored);
$conn->close();
if($missing || !$accountOk) { http_response_code(500); exit("Verifikasi gagal. Tabel kurang: ".htmlspecialchars(implode(", ",$missing),ENT_QUOTES,"UTF-8").". Akun admin: ".($accountOk?"OK":"gagal")); }
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup Database SDN 1 Beririjarak</title></head>
<body style="font-family:Arial,sans-serif;max-width:700px;margin:50px auto;padding:24px;line-height:1.7">
<h2>Database berhasil disiapkan dan diverifikasi</h2>
<p>Database: <b>db_sdn1beririjarak_baru</b></p>
<p>Delapan tabel berhasil dibuat atau diperiksa.</p>
<p>Akun admin: <b>Admin2026</b> / <b>AdminIPA</b></p>
<p><a href="test_db.php">Uji seluruh database</a> · <a href="login.php">Buka login</a></p>
<p>Setelah instalasi selesai, hapus atau ubah nama file setup_database.php demi keamanan.</p>
</body></html>