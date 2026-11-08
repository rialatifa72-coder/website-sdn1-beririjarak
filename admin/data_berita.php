<?php
session_start();
if (!isset($_SESSION["username"])) { header("Location: ../login.php"); exit; }
require_once __DIR__ . "/../koneksi.php";
if (!$koneksi) die("Koneksi database gagal. Periksa koneksi.php.");

function e($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
if (empty($_SESSION["csrf_berita"])) $_SESSION["csrf_berita"] = bin2hex(random_bytes(32));
$pesan = ""; $jenisPesan = "success";
$folderUpload = __DIR__ . "/../uploads/berita/";
$prefixUpload = "uploads/berita/";
if (!is_dir($folderUpload) && !mkdir($folderUpload, 0755, true) && !is_dir($folderUpload)) {
    die("Folder uploads/berita tidak dapat dibuat.");
}

function simpanGambarBerita($folder, $prefix, $gambarLama = "") {
    if (!isset($_FILES["gambar"]) || $_FILES["gambar"]["error"] === UPLOAD_ERR_NO_FILE) return $gambarLama;
    if ($_FILES["gambar"]["error"] !== UPLOAD_ERR_OK) throw new RuntimeException("Gambar gagal diunggah.");
    if ($_FILES["gambar"]["size"] > 4 * 1024 * 1024) throw new RuntimeException("Ukuran gambar maksimal 4 MB.");
    $info = getimagesize($_FILES["gambar"]["tmp_name"]);
    if ($info === false) throw new RuntimeException("File harus berupa gambar yang valid.");
    $mime = $info["mime"] ?? "";
    $ext = ["image/jpeg"=>"jpg", "image/png"=>"png", "image/webp"=>"webp"];
    if (!isset($ext[$mime])) throw new RuntimeException("Format gambar harus JPG, PNG, atau WEBP.");
    $nama = bin2hex(random_bytes(12)) . "." . $ext[$mime];
    if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $folder . $nama)) throw new RuntimeException("Gambar tidak dapat disimpan.");
    if ($gambarLama && strpos($gambarLama, $prefix) === 0) {
        $lama = __DIR__ . "/../" . $gambarLama;
        if (is_file($lama)) @unlink($lama);
    }
    return $prefix . $nama;
}
function hapusGambarBerita($path) {
    if ($path && strpos($path, "uploads/berita/") === 0) {
        $file = __DIR__ . "/../" . $path;
        if (is_file($file)) @unlink($file);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (!hash_equals($_SESSION["csrf_berita"], $_POST["csrf"] ?? "")) throw new RuntimeException("Sesi formulir tidak valid. Muat ulang halaman.");
        $aksi = $_POST["aksi"] ?? "";
        if ($aksi === "hapus") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id < 1) throw new RuntimeException("ID berita tidak valid.");
            $cek = mysqli_prepare($koneksi, "SELECT gambar FROM tb_berita WHERE id=?");
            mysqli_stmt_bind_param($cek, "i", $id);
            mysqli_stmt_execute($cek);
            $lama = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
            mysqli_stmt_close($cek);
            if (!$lama) throw new RuntimeException("Berita tidak ditemukan.");
            $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_berita WHERE id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Berita gagal dihapus.");
            mysqli_stmt_close($stmt);
            hapusGambarBerita($lama["gambar"] ?? "");
            $pesan = "Berita berhasil dihapus.";
        } elseif ($aksi === "simpan" || $aksi === "ubah") {
            $id = (int)($_POST["id"] ?? 0);
            $judul = trim($_POST["judul"] ?? "");
            $isi = trim($_POST["isi"] ?? "");
            $tanggal = trim($_POST["tanggal_publikasi"] ?? "");
            $status = $_POST["status"] ?? "Draft";
            if ($judul === "" || $isi === "") throw new RuntimeException("Judul dan isi berita wajib diisi.");
            if (!in_array($status, ["Draft", "Terbit"], true)) throw new RuntimeException("Status berita tidak valid.");
            if ($tanggal === "") $tanggal = date("Y-m-d\TH:i");
            $dt = DateTime::createFromFormat("Y-m-d\TH:i", $tanggal);
            if (!$dt || $dt->format("Y-m-d\TH:i") !== $tanggal) throw new RuntimeException("Tanggal publikasi tidak valid.");
            $tanggalDb = $dt->format("Y-m-d H:i:s");
            $slug = trim(strtolower(preg_replace("/[^a-z0-9]+/i", "-", iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $judul))), "-");
            if ($slug === "") $slug = "berita-" . time();
            $gambarLama = "";
            if ($aksi === "ubah") {
                if ($id < 1) throw new RuntimeException("ID berita tidak valid.");
                $cek = mysqli_prepare($koneksi, "SELECT gambar FROM tb_berita WHERE id=?");
                mysqli_stmt_bind_param($cek, "i", $id);
                mysqli_stmt_execute($cek);
                $dataLama = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
                mysqli_stmt_close($cek);
                if (!$dataLama) throw new RuntimeException("Berita tidak ditemukan.");
                $gambarLama = $dataLama["gambar"] ?? "";
            }
            $gambar = simpanGambarBerita($folderUpload, $prefixUpload, $gambarLama);
            if ($aksi === "simpan") {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_berita (judul,slug,isi,gambar,tanggal_publikasi,status) VALUES (?,?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt, "ssssss", $judul, $slug, $isi, $gambar, $tanggalDb, $status);
            } else {
                $stmt = mysqli_prepare($koneksi, "UPDATE tb_berita SET judul=?,slug=?,isi=?,gambar=?,tanggal_publikasi=?,status=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssssi", $judul, $slug, $isi, $gambar, $tanggalDb, $status, $id);
            }
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Berita gagal disimpan: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            $pesan = $aksi === "simpan" ? "Berita berhasil ditambahkan." : "Berita berhasil diperbarui.";
        }
    } catch (Throwable $err) {
        $pesan = $err->getMessage();
        $jenisPesan = "danger";
    }
}

$editData = null;
if (isset($_GET["edit"])) {
    $idEdit = (int)$_GET["edit"];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_berita WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $idEdit);
    mysqli_stmt_execute($stmt);
    $editData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$editData) { $pesan = "Berita tidak ditemukan."; $jenisPesan = "warning"; }
}
$q = trim($_GET["q"] ?? "");
if ($q !== "") {
    $like = "%" . $q . "%";
    $stmt = mysqli_prepare($koneksi, "SELECT id,judul,gambar,tanggal_publikasi,status FROM tb_berita WHERE judul LIKE ? OR isi LIKE ? ORDER BY tanggal_publikasi DESC,id DESC");
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
    mysqli_stmt_execute($stmt);
    $daftar = mysqli_stmt_get_result($stmt);
} else {
    $daftar = mysqli_query($koneksi, "SELECT id,judul,gambar,tanggal_publikasi,status FROM tb_berita ORDER BY tanggal_publikasi DESC,id DESC");
}
if (!$daftar) die("Gagal mengambil berita: " . e(mysqli_error($koneksi)));
?>
<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CRUD Berita | SDN 1 Beririjarak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f3f7fc;color:#26364a;font-family:Segoe UI,Tahoma,sans-serif}.topbar{background:#082b59;color:#fff;padding:17px 24px}.wrap{max-width:1250px;margin:28px auto;padding:0 16px}.panel{background:#fff;border:0;border-radius:14px;box-shadow:0 3px 16px rgba(20,50,90,.07);padding:24px}.judul{color:#082b59;font-weight:700}.table th{background:#edf4fc;white-space:nowrap}.thumb{width:76px;height:55px;object-fit:cover;border-radius:8px}
</style></head><body>
<header class="topbar"><div class="container-fluid d-flex justify-content-between"><strong><i class="bi bi-mortarboard-fill me-2"></i>SDN 1 BERIRIJARAK — ADMIN</strong><span>Halo, <?= e($_SESSION["username"]) ?></span></div></header>
<main class="wrap">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 judul mb-1">Pengelolaan Berita Sekolah</h1><p class="text-secondary mb-0">Tambah, lihat, ubah, hapus, dan terbitkan berita atau pengumuman sekolah.</p></div><a href="../dashboard_admin.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Dashboard</a></div>
<?php if ($pesan): ?><div class="alert alert-<?= e($jenisPesan) ?> alert-dismissible fade show"><?= e($pesan) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<section class="panel mb-4">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h2 class="h5 judul mb-0"><?= $editData ? "Ubah Berita" : "Tambah Berita Baru" ?></h2><span class="text-muted small">Kolom bertanda * wajib diisi</span></div>
<form method="post" enctype="multipart/form-data" class="row g-3">
<input type="hidden" name="csrf" value="<?= e($_SESSION["csrf_berita"]) ?>"><input type="hidden" name="aksi" value="<?= $editData ? "ubah" : "simpan" ?>">
<?php if ($editData): ?><input type="hidden" name="id" value="<?= (int)$editData["id"] ?>"><?php endif; ?>
<div class="col-lg-8"><label class="form-label">Judul Berita <span class="text-danger">*</span></label><input class="form-control" name="judul" maxlength="200" required value="<?= e($editData["judul"] ?? "") ?>"></div>
<div class="col-lg-4"><label class="form-label">Status Publikasi</label><select class="form-select" name="status"><?php foreach (["Draft","Terbit"] as $st): ?><option value="<?= e($st) ?>" <?= ($editData["status"] ?? "Draft") === $st ? "selected" : "" ?>><?= e($st) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Tanggal Publikasi</label><input type="datetime-local" class="form-control" name="tanggal_publikasi" value="<?= !empty($editData["tanggal_publikasi"]) ? e(date("Y-m-d\TH:i", strtotime($editData["tanggal_publikasi"]))) : e(date("Y-m-d\TH:i")) ?>"></div>
<div class="col-md-6"><label class="form-label">Gambar Berita (JPG/PNG/WEBP, maks. 4 MB)</label><input type="file" class="form-control" name="gambar" accept=".jpg,.jpeg,.png,.webp"><?php if (!empty($editData["gambar"])): ?><small class="text-muted">Gambar saat ini: <?= e($editData["gambar"]) ?></small><?php endif; ?></div>
<div class="col-12"><label class="form-label">Isi Berita <span class="text-danger">*</span></label><textarea class="form-control" name="isi" rows="7" required><?= e($editData["isi"] ?? "") ?></textarea><div class="form-text">Teks berita ditampilkan sebagai paragraf di halaman utama.</div></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i><?= $editData ? "Simpan Perubahan" : "Tambah Berita" ?></button><?php if ($editData): ?><a href="data_berita.php" class="btn btn-light">Batal</a><?php endif; ?></div>
</form>
</section>
<section class="panel">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 judul mb-0">Daftar Berita</h2><span class="badge text-bg-primary"><?= mysqli_num_rows($daftar) ?> data</span></div>
<form method="get" class="row g-2 mb-3"><div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari judul atau isi berita..."></div><div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button></div><?php if ($q !== ""): ?><div class="col-auto"><a href="data_berita.php" class="btn btn-light">Reset</a></div><?php endif; ?></form>
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>No.</th><th>Gambar</th><th>Judul</th><th>Tanggal Publikasi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if (mysqli_num_rows($daftar) === 0): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada berita.</td></tr><?php else: $no=1; while ($b=mysqli_fetch_assoc($daftar)): ?>
<tr><td><?= $no++ ?></td><td><?php if (!empty($b["gambar"])): ?><img class="thumb" src="../<?= e($b["gambar"]) ?>" alt="Gambar berita"><?php else: ?><span class="text-muted">—</span><?php endif; ?></td><td class="fw-semibold"><?= e($b["judul"]) ?></td><td><?= e(date("d-m-Y H:i", strtotime($b["tanggal_publikasi"]))) ?></td><td><span class="badge <?= $b["status"] === "Terbit" ? "text-bg-success" : "text-bg-secondary" ?>"><?= e($b["status"]) ?></span></td><td class="text-nowrap"><a class="btn btn-sm btn-warning" href="data_berita.php?edit=<?= (int)$b["id"] ?>"><i class="bi bi-pencil"></i> Edit</a> <form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus berita ini?')"><input type="hidden" name="csrf" value="<?= e($_SESSION["csrf_berita"]) ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$b["id"] ?>"><button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button></form></td></tr>
<?php endwhile; endif; ?>
</tbody></table></div>
</section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
