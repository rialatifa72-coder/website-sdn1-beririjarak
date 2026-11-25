<?php
session_start();
if (!isset($_SESSION["username"])) { header("Location: ../login.php"); exit; }
require_once __DIR__ . "/../koneksi.php";
if (!$koneksi) die("Koneksi database gagal. Periksa koneksi.php.");
function e($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
if (empty($_SESSION["csrf_fasilitas"])) $_SESSION["csrf_fasilitas"] = bin2hex(random_bytes(32));
$pesan = ""; $jenisPesan = "success";
$folder = __DIR__ . "/../uploads/fasilitas/";
$prefix = "uploads/fasilitas/";
if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) die("Folder upload tidak dapat dibuat.");
$buatTabel = mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS tb_fasilitas (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nama_fasilitas VARCHAR(150) NOT NULL,
 deskripsi TEXT DEFAULT NULL,
 foto VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
if (!$buatTabel) die("Tabel fasilitas gagal dibuat: " . e(mysqli_error($koneksi)));
function hapusFoto($path) {
 if ($path && strpos($path, "uploads/fasilitas/") === 0) {
  $file = __DIR__ . "/../" . $path;
  if (is_file($file)) @unlink($file);
 }
}
function uploadFoto($folder, $prefix, $lama = "") {
 if (!isset($_FILES["foto"]) || $_FILES["foto"]["error"] === UPLOAD_ERR_NO_FILE) return $lama;
 if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) throw new RuntimeException("Foto gagal diunggah.");
 if ($_FILES["foto"]["size"] > 5 * 1024 * 1024) throw new RuntimeException("Ukuran foto maksimal 5 MB.");
 $info = @getimagesize($_FILES["foto"]["tmp_name"]);
 if (!$info) throw new RuntimeException("File harus berupa gambar yang valid.");
 $ext = ["image/jpeg"=>"jpg", "image/png"=>"png", "image/webp"=>"webp"];
 if (!isset($ext[$info["mime"] ?? ""])) throw new RuntimeException("Format foto harus JPG, PNG, atau WEBP.");
 $nama = bin2hex(random_bytes(12)) . "." . $ext[$info["mime"]];
 if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $folder . $nama)) throw new RuntimeException("Foto tidak dapat disimpan.");
 if ($lama) hapusFoto($lama);
 return $prefix . $nama;
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
 try {
  if (!hash_equals($_SESSION["csrf_fasilitas"], $_POST["csrf"] ?? "")) throw new RuntimeException("Sesi formulir tidak valid. Muat ulang halaman.");
  $aksi = $_POST["aksi"] ?? "";
  $id = (int)($_POST["id"] ?? 0);
  if ($aksi === "hapus") {
   $stmt = mysqli_prepare($koneksi, "SELECT foto FROM tb_fasilitas WHERE id=?");
   mysqli_stmt_bind_param($stmt, "i", $id); mysqli_stmt_execute($stmt);
   $lama = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
   if (!$lama) throw new RuntimeException("Data fasilitas tidak ditemukan.");
   $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_fasilitas WHERE id=?");
   mysqli_stmt_bind_param($stmt, "i", $id);
   if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Data gagal dihapus.");
   mysqli_stmt_close($stmt); hapusFoto($lama["foto"]); $pesan = "Data fasilitas berhasil dihapus.";
  } elseif ($aksi === "simpan" || $aksi === "ubah") {
   $nama = trim($_POST["nama_fasilitas"] ?? "");
   $deskripsi = trim($_POST["deskripsi"] ?? "");
   if ($nama === "") throw new RuntimeException("Nama fasilitas wajib diisi.");
   $lama = "";
   if ($aksi === "ubah") {
    $stmt = mysqli_prepare($koneksi, "SELECT foto FROM tb_fasilitas WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id); mysqli_stmt_execute($stmt);
    $dataLama = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
    if (!$dataLama) throw new RuntimeException("Data fasilitas tidak ditemukan.");
    $lama = $dataLama["foto"];
   }
   $foto = uploadFoto($folder, $prefix, $lama);
   if (!$foto) throw new RuntimeException("Silakan pilih foto fasilitas.");
   if ($aksi === "simpan") {
    $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_fasilitas (nama_fasilitas,deskripsi,foto) VALUES (?,?,?)");
    mysqli_stmt_bind_param($stmt, "sss", $nama, $deskripsi, $foto);
   } else {
    $stmt = mysqli_prepare($koneksi, "UPDATE tb_fasilitas SET nama_fasilitas=?,deskripsi=?,foto=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssi", $nama, $deskripsi, $foto, $id);
   }
   if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Data gagal disimpan.");
   mysqli_stmt_close($stmt); $pesan = $aksi === "simpan" ? "Foto fasilitas berhasil ditambahkan." : "Data berhasil diperbarui.";
  }
 } catch (Throwable $err) { $pesan = $err->getMessage(); $jenisPesan = "danger"; }
}
$edit = null;
if (isset($_GET["edit"])) {
 $idEdit = (int)$_GET["edit"];
 $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_fasilitas WHERE id=?");
 mysqli_stmt_bind_param($stmt, "i", $idEdit); mysqli_stmt_execute($stmt);
 $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
 if (!$edit) { $pesan = "Data tidak ditemukan."; $jenisPesan = "warning"; }
}
$daftar = mysqli_query($koneksi, "SELECT * FROM tb_fasilitas ORDER BY id DESC");
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Fasilitas Sekolah | SDN 1 Beririjarak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body{background:#f3f7fc;color:#26364a;font-family:Segoe UI,Tahoma,sans-serif}.topbar{background:#082b59;color:#fff;padding:17px 24px}.wrap{max-width:1250px;margin:28px auto;padding:0 16px}.panel{background:#fff;border-radius:14px;padding:24px;box-shadow:0 3px 16px rgba(20,50,90,.07)}.judul{color:#082b59;font-weight:700}.thumb{width:110px;height:78px;object-fit:cover;border-radius:8px}.preview{max-width:180px;max-height:130px;object-fit:cover;border-radius:10px}.admin-brand{display:flex;align-items:center;gap:12px}.admin-brand img{width:44px;height:44px;object-fit:contain;background:#fff;border-radius:10px;padding:3px}@media(max-width:575px){.topbar{padding:12px 14px}}</style></head><body>
<header class="topbar"><div class="container-fluid d-flex justify-content-between align-items-center"><div class="admin-brand"><img src="../logo.jpeg" alt="Logo"><strong>SDN 1 BERIRIJARAK — ADMIN</strong></div><span>Halo, <?=e($_SESSION["username"])?></span></div></header>
<main class="wrap"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 judul mb-1">Pengelolaan Fasilitas Sekolah Sekolah</h1><p class="text-secondary mb-0">Tambahkan foto dan informasi fasilitas fasilitas sekolah.</p></div><a href="../dashboard_admin.php?page=beranda" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Dashboard</a></div>
<?php if($pesan):?><div class="alert alert-<?=e($jenisPesan)?> alert-dismissible fade show"><?=e($pesan)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="panel mb-4"><h2 class="h5 judul mb-3"><?=$edit?"Ubah Data Fasilitas Sekolah":"Tambah Foto Fasilitas Sekolah"?></h2>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e($_SESSION["csrf_fasilitas"])?>"><input type="hidden" name="aksi" value="<?=$edit?"ubah":"simpan"?>"><?php if($edit):?><input type="hidden" name="id" value="<?=(int)$edit["id"]?>"><?php endif;?>
<div class="row g-3"><div class="col-md-6"><label class="form-label">Nama Fasilitas</label><input class="form-control" name="nama_fasilitas" required maxlength="150" value="<?=e($edit["nama_fasilitas"]??"")?>" placeholder="Contoh: Pramuka"></div>
<div class="col-md-6"><label class="form-label">Foto Fasilitas <?=$edit?"(pilih jika ingin mengganti)":""?></label><input class="form-control" type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" <?=$edit?"":"required"?>><small class="text-muted">Format JPG, PNG, WEBP. Maksimal 5 MB.</small><?php if($edit):?><div class="mt-2"><img class="preview" src="../<?=e($edit["foto"])?>" alt="Foto saat ini"></div><?php endif;?></div>
<div class="col-12"><label class="form-label">Deskripsi</label><textarea class="form-control" rows="3" name="deskripsi" placeholder="Keterangan singkat fasilitas..."><?=e($edit["deskripsi"]??"")?></textarea></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i><?=$edit?"Simpan Perubahan":"Tambah Foto"?></button><?php if($edit):?><a href="data_fasilitas.php" class="btn btn-outline-secondary">Batal</a><?php endif;?></div></div></form></div>
<div class="panel"><h2 class="h5 judul mb-3">Daftar Foto Fasilitas</h2><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Foto</th><th>Nama Fasilitas</th><th>Deskripsi</th><th>Aksi</th></tr></thead><tbody>
<?php if(mysqli_num_rows($daftar)===0):?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada foto fasilitas. Silakan tambahkan melalui formulir di atas.</td></tr><?php else: while($row=mysqli_fetch_assoc($daftar)):?><tr><td><img class="thumb" src="../<?=e($row["foto"])?>" alt="<?=e($row["nama_fasilitas"])?>"></td><td class="fw-semibold"><?=e($row["nama_fasilitas"])?></td><td><?=e($row["deskripsi"])?></td><td class="text-nowrap"><a class="btn btn-sm btn-warning" href="?edit=<?=(int)$row["id"]?>"><i class="bi bi-pencil"></i> Ubah</a> <form method="post" class="d-inline" onsubmit="return confirm('Hapus data fasilitas ini?')"><input type="hidden" name="csrf" value="<?=e($_SESSION["csrf_fasilitas"])?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?=(int)$row["id"]?>"><button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button></form></td></tr><?php endwhile; endif;?>
</tbody></table></div></div></main></body></html>