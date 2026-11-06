<?php
require_once __DIR__ . "/koneksi.php";
header("Content-Type: text/plain; charset=utf-8");
if (!$koneksi) {
 http_response_code(500);
 exit("Koneksi gagal: " . $koneksi_error);
}
$tables=["tb_users","tb_guru","tb_siswa","tb_berita","tb_galeri","tb_media","tb_profil","tb_pendaftaran"];
$missing=[];
foreach($tables as $table) {
 $res=mysqli_query($koneksi,"SHOW TABLES LIKE '".mysqli_real_escape_string($koneksi,$table)."'");
 if(!$res || mysqli_num_rows($res)!==1) $missing[]=$table;
}
if($missing) { http_response_code(500); exit("Tabel belum lengkap: ".implode(", ",$missing).". Jalankan setup_database.php."); }
$stmt=mysqli_prepare($koneksi,"SELECT password FROM tb_users WHERE username='Admin2026' LIMIT 1");
if(!$stmt || !mysqli_stmt_execute($stmt)) { http_response_code(500); exit("Pemeriksaan akun admin gagal."); }
mysqli_stmt_bind_result($stmt,$storedPassword);
$found=mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
if(!$found) { http_response_code(500); exit("Akun Admin2026 belum tersedia. Jalankan setup_database.php."); }
if(!password_verify("AdminIPA",(string)$storedPassword)) {
 http_response_code(500);
 exit("Akun ditemukan, tetapi password belum sesuai dengan kredensial installer. Jalankan ulang setup_database.php untuk menyamakan password admin.");
}
echo "BERHASIL: database db_sdn1beririjarak_baru terhubung, 8 tabel tersedia, dan akun Admin2026 / AdminIPA terverifikasi.";
