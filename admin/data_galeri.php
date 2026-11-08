<?php
session_start();
if (!isset($_SESSION["username"])) { header("Location: ../login.php"); exit; }
require_once __DIR__ . "/../koneksi.php";
if (!$koneksi) die("Koneksi database gagal. Periksa koneksi.php.");

function e($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
if (empty($_SESSION["csrf_galeri"])) $_SESSION["csrf_galeri"] = bin2hex(random_bytes(32));
$pesan = ""; $jenisPesan = "success";
$folderUpload = __DIR__ . "/../uploads/galeri/";
$prefixUpload = "uploads/galeri/";
if (!is_dir($folderUpload) && !mkdir($folderUpload, 0755, true) && !is_dir($folderUpload)) {
    die("Folder uploads/galeri tidak dapat dibuat.");
}
function hapusFotoGaleri($path) {
    if ($path && strpos($path, "uploads/galeri/") === 0) {
        $file = __DIR__ . "/../" . $path;
        if (is_file($file)) @unlink($file);
    }
}
function simpanFotoGaleri($folder, $prefix, $lama = "") {
    if (!isset($_FILES["foto"]) || $_FILES["foto"]["error"] === UPLOAD_ERR_NO_FILE) return $lama;
    if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) throw new RuntimeException("Foto gagal diunggah.");
    if ($_FILES["foto"]["size"] > 5 * 1024 * 1024) throw new RuntimeException("Ukuran foto maksimal 5 MB.");
    $info = @getimagesize($_FILES["foto"]["tmp_name"]);
    if ($info === false) throw new RuntimeException("File harus berupa gambar yang valid.");
    $mime = $info["mime"] ?? "";
    $ext = ["image/jpeg"=>"jpg", "image/png"=>"png", "image/webp"=>"webp"];
    if (!isset($ext[$mime])) throw new RuntimeException("Format foto harus JPG, PNG, atau WEBP.");
    $nama = bin2hex(random_bytes(12)) . "." . $ext[$mime];
    if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $folder . $nama)) throw new RuntimeException("Foto tidak dapat disimpan.");
    if ($lama && $lama !== $prefix . $nama) hapusFotoGaleri($lama);
    return $prefix . $nama;
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (!hash_equals($_SESSION["csrf_galeri"], $_POST["csrf"] ?? "")) throw new RuntimeException("Sesi formulir tidak valid. Muat ulang halaman.");
        $aksi = $_POST["aksi"] ?? "";
        if ($aksi === "hapus") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id < 1) throw new RuntimeException("ID foto tidak valid.");
            $cek = mysqli_prepare($koneksi, "SELECT nama_file FROM tb_galeri WHERE id=?");
            mysqli_stmt_bind_param($cek, "i", $id);
            mysqli_stmt_execute($cek);
            $lama = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
            mysqli_stmt_close($cek);
            if (!$lama) throw new RuntimeException("Foto galeri tidak ditemukan.");
            $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_galeri WHERE id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Foto gagal dihapus.");
            mysqli_stmt_close($stmt);
            hapusFotoGaleri($lama["nama_file"] ?? "");
            $pesan = "Foto galeri berhasil dihapus.";
        } elseif ($aksi === "simpan" || $aksi === "ubah") {
            $id = (int)($_POST["id"] ?? 0);
            $judul = trim($_POST["judul"] ?? "");
            $deskripsi = trim($_POST["deskripsi"] ?? "");
            $tanggal = trim($_POST["tanggal_kegiatan"] ?? "");
            if ($judul === "") throw new RuntimeException("Judul foto wajib diisi.");
            if ($tanggal !== "") {
                $dt = DateTime::createFromFormat("Y-m-d", $tanggal);
                if (!$dt || $dt->format("Y-m-d") !== $tanggal) throw new RuntimeException("Tanggal kegiatan tidak valid.");
            } else { $tanggal = null; }
            $fotoLama = "";
            if ($aksi === "ubah") {
                if ($id < 1) throw new RuntimeException("ID foto tidak valid.");
                $cek = mysqli_prepare($koneksi, "SELECT nama_file FROM tb_galeri WHERE id=?");
                mysqli_stmt_bind_param($cek, "i", $id);
                mysqli_stmt_execute($cek);
                $dataLama = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
                mysqli_stmt_close($cek);
                if (!$dataLama) throw new RuntimeException("Foto galeri tidak ditemukan.");
                $fotoLama = $dataLama["nama_file"] ?? "";
            }
            $foto = simpanFotoGaleri($folderUpload, $prefixUpload, $fotoLama);
            if ($foto === "") throw new RuntimeException("Silakan pilih foto untuk galeri.");
            if ($aksi === "simpan") {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_galeri (judul,deskripsi,nama_file,tanggal_kegiatan) VALUES (?,?,?,?)");
                mysqli_stmt_bind_param($stmt, "ssss", $judul, $deskripsi, $foto, $tanggal);
            } else {
                $stmt = mysqli_prepare($koneksi, "UPDATE tb_galeri SET judul=?,deskripsi=?,nama_file=?,tanggal_kegiatan=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssi", $judul, $deskripsi, $foto, $tanggal, $id);
            }
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Data galeri gagal disimpan: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            $pesan = $aksi === "simpan" ? "Foto berhasil ditambahkan." : "Data foto berhasil diperbarui.";
        }
    } catch (Throwable $err) {
        $pesan = $err->getMessage();
        $jenisPesan = "danger";
    }
}
$editData = null;
if (isset($_GET["edit"])) {
    $idEdit = (int)$_GET["edit"];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_galeri WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $idEdit);
    mysqli_stmt_execute($stmt);
    $editData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$editData) { $pesan = "Foto galeri tidak ditemukan."; $jenisPesan = "warning"; }
}
$q = trim($_GET["q"] ?? "");
if ($q !== "") {
    $like = "%" . $q . "%";
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_galeri WHERE judul LIKE ? OR deskripsi LIKE ? ORDER BY tanggal_kegiatan DESC,id DESC");
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
    mysqli_stmt_execute($stmt);
    $daftar = mysqli_stmt_get_result($stmt);
} else {
    $daftar = mysqli_query($koneksi, "SELECT * FROM tb_galeri ORDER BY tanggal_kegiatan DESC,id DESC");
}
if (!$daftar) die("Gagal mengambil data galeri: " . e(mysqli_error($koneksi)));
?>
<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CRUD Galeri | SDN 1 Beririjarak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f3f7fc;color:#26364a;font-family:Segoe UI,Tahoma,sans-serif}.topbar{background:#082b59;color:#fff;padding:17px 24px}.wrap{max-width:1250px;margin:28px auto;padding:0 16px}.panel{background:#fff;border:0;border-radius:14px;box-shadow:0 3px 16px rgba(20,50,90,.07);padding:24px}.judul{color:#082b59;font-weight:700}.table th{background:#edf4fc;white-space:nowrap}.thumb{width:105px;height:72px;object-fit:cover;border-radius:8px}
</style></head><body>
<header class="topbar"><div class="container-fluid d-flex justify-content-between"><strong><i class="bi bi-mortarboard-fill me-2"></i>SDN 1 BERIRIJARAK — ADMIN</strong><span>Halo, <?= e($_SESSION["username"]) ?></span></div></header>
<main class="wrap">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 judul mb-1">Pengelolaan Galeri Foto</h1><p class="text-secondary mb-0">Kelola dokumentasi foto kegiatan SDN 1 Beririjarak.</p></div><a href="../dashboard_admin.php?page=beranda" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Dashboard</a></div>
<?php if ($pesan): ?><div class="alert alert-<?= e($jenisPesan) ?> alert-dismissible fade show"><?= e($pesan) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<section class="panel mb-4">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h2 class="h5 judul mb-0"><?= $editData ? "Ubah Foto Galeri" : "Tambah Foto Baru" ?></h2><span class="text-muted small">Judul wajib diisi. Foto maksimal 5 MB.</span></div>
<form method="post" enctype="multipart/form-data" class="row g-3">
<input type="hidden" name="csrf" value="<?= e($_SESSION["csrf_galeri"]) ?>"><input type="hidden" name="aksi" value="<?= $editData ? "ubah" : "simpan" ?>">
<?php if ($editData): ?><input type="hidden" name="id" value="<?= (int)$editData["id"] ?>"><?php endif; ?>
<div class="col-md-7"><label class="form-label">Judul Foto <span class="text-danger">*</span></label><input class="form-control" name="judul" maxlength="150" required value="<?= e($editData["judul"] ?? "") ?>" placeholder="Contoh: Kegiatan Upacara Bendera"></div>
<div class="col-md-5"><label class="form-label">Tanggal Kegiatan</label><input type="date" class="form-control" name="tanggal_kegiatan" value="<?= e($editData["tanggal_kegiatan"] ?? "") ?>"></div>
<div class="col-12"><label class="form-label">Keterangan</label><textarea class="form-control" name="deskripsi" rows="3" placeholder="Tuliskan keterangan singkat kegiatan..."><?= e($editData["deskripsi"] ?? "") ?></textarea></div>
<div class="col-md-8"><label class="form-label">Foto (JPG/PNG/WEBP, maks. 5 MB) <?= $editData ? "" : "<span class='text-danger'>*</span>" ?></label><input type="file" class="form-control" name="foto" accept=".jpg,.jpeg,.png,.webp" <?= $editData ? "" : "required" ?>><?php if (!empty($editData["nama_file"])): ?><div class="mt-2"><img class="thumb" src="../<?= e($editData["nama_file"]) ?>" alt="Foto saat ini"></div><?php endif; ?></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i><?= $editData ? "Simpan Perubahan" : "Tambah Foto" ?></button><?php if ($editData): ?><a href="data_galeri.php" class="btn btn-light">Batal</a><?php endif; ?></div>
</form>
</section>
<section class="panel">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 judul mb-0">Daftar Foto Galeri</h2><span class="badge text-bg-primary"><?= mysqli_num_rows($daftar) ?> foto</span></div>
<form method="get" class="row g-2 mb-3"><div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari judul atau keterangan foto..."></div><div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button></div><?php if ($q !== ""): ?><div class="col-auto"><a href="data_galeri.php" class="btn btn-light">Reset</a></div><?php endif; ?></form>
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>No.</th><th>Foto</th><th>Judul</th><th>Tanggal Kegiatan</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
<?php if (mysqli_num_rows($daftar) === 0): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada foto galeri.</td></tr><?php else: $no=1; while ($g=mysqli_fetch_assoc($daftar)): ?>
<tr><td><?= $no++ ?></td><td><img class="thumb" src="../<?= e($g["nama_file"]) ?>" alt="<?= e($g["judul"]) ?>"></td><td class="fw-semibold"><?= e($g["judul"]) ?></td><td><?= !empty($g["tanggal_kegiatan"]) ? e(date("d-m-Y", strtotime($g["tanggal_kegiatan"]))) : "—" ?></td><td><?= e($g["deskripsi"]) ?></td><td class="text-nowrap"><a class="btn btn-sm btn-warning" href="data_galeri.php?edit=<?= (int)$g["id"] ?>"><i class="bi bi-pencil"></i> Edit</a> <form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus foto ini?')"><input type="hidden" name="csrf" value="<?= e($_SESSION["csrf_galeri"]) ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$g["id"] ?>"><button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button></form></td></tr>
<?php endwhile; endif; ?>
</tbody></table></div>
</section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
