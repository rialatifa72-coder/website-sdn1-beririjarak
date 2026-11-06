<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit;
}
require_once __DIR__ . "/../koneksi.php";

if (!$koneksi) {
    die("Koneksi database gagal. Periksa koneksi.php.");
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["csrf_guru"])) {
    $_SESSION["csrf_guru"] = bin2hex(random_bytes(32));
}
$pesan = "";
$jenisPesan = "success";
$folderUpload = __DIR__ . "/../uploads/guru/";
$prefixUpload = "uploads/guru/";

if (!is_dir($folderUpload)) {
    mkdir($folderUpload, 0755, true);
}

function simpanFotoGuru($input, $folderUpload, $prefixUpload, $fotoLama = "") {
    if (!isset($_FILES[$input]) || $_FILES[$input]["error"] === UPLOAD_ERR_NO_FILE) {
        return $fotoLama;
    }
    if ($_FILES[$input]["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Foto gagal diunggah.");
    }
    if ($_FILES[$input]["size"] > 3 * 1024 * 1024) {
        throw new RuntimeException("Ukuran foto maksimal 3 MB.");
    }
    $info = getimagesize($_FILES[$input]["tmp_name"]);
    if ($info === false) {
        throw new RuntimeException("File harus berupa gambar yang valid.");
    }
    $mime = $info["mime"] ?? "";
    $ekstensi = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
    if (!isset($ekstensi[$mime])) {
        throw new RuntimeException("Format foto harus JPG, PNG, atau WEBP.");
    }
    $namaFile = bin2hex(random_bytes(12)) . "." . $ekstensi[$mime];
    if (!move_uploaded_file($_FILES[$input]["tmp_name"], $folderUpload . $namaFile)) {
        throw new RuntimeException("Foto tidak dapat disimpan ke folder uploads/guru.");
    }
    if ($fotoLama && strpos($fotoLama, $prefixUpload) === 0) {
        $lama = __DIR__ . "/../" . $fotoLama;
        if (is_file($lama)) @unlink($lama);
    }
    return $prefixUpload . $namaFile;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (!hash_equals($_SESSION["csrf_guru"], $_POST["csrf"] ?? "")) {
            throw new RuntimeException("Sesi formulir tidak valid. Muat ulang halaman.");
        }
        $aksi = $_POST["aksi"] ?? "";
        if ($aksi === "hapus") {
            $id = (int)($_POST["id"] ?? 0);
            $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_guru WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $pesan = "Data guru berhasil dihapus.";
        } elseif ($aksi === "simpan" || $aksi === "ubah") {
            $id = (int)($_POST["id"] ?? 0);
            $nip = trim($_POST["nip"] ?? "");
            $nama = trim($_POST["nama_guru"] ?? "");
            $jk = $_POST["jenis_kelamin"] ?? "";
            $jabatan = trim($_POST["jabatan"] ?? "");
            $pendidikan = trim($_POST["pendidikan"] ?? "");
            if ($nama === "") throw new RuntimeException("Nama guru wajib diisi.");
            if (!in_array($jk, ["", "Laki-laki", "Perempuan"], true)) throw new RuntimeException("Jenis kelamin tidak valid.");

            $fotoLama = "";
            if ($aksi === "ubah") {
                $cek = mysqli_prepare($koneksi, "SELECT foto FROM tb_guru WHERE id = ?");
                mysqli_stmt_bind_param($cek, "i", $id);
                mysqli_stmt_execute($cek);
                mysqli_stmt_bind_result($cek, $fotoLama);
                if (!mysqli_stmt_fetch($cek)) throw new RuntimeException("Data guru tidak ditemukan.");
                mysqli_stmt_close($cek);
            }
            $foto = simpanFotoGuru("foto", $folderUpload, $prefixUpload, $fotoLama);
            if ($aksi === "simpan") {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_guru (nip, nama_guru, jenis_kelamin, jabatan, pendidikan, foto) VALUES (?, ?, NULLIF(?, ''), ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssss", $nip, $nama, $jk, $jabatan, $pendidikan, $foto);
            } else {
                $stmt = mysqli_prepare($koneksi, "UPDATE tb_guru SET nip=?, nama_guru=?, jenis_kelamin=NULLIF(?, ''), jabatan=?, pendidikan=?, foto=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssssi", $nip, $nama, $jk, $jabatan, $pendidikan, $foto, $id);
            }
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Data gagal disimpan: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            $pesan = $aksi === "simpan" ? "Data guru berhasil ditambahkan." : "Data guru berhasil diperbarui.";
        }
    } catch (Throwable $err) {
        $pesan = $err->getMessage();
        $jenisPesan = "danger";
    }
}

$editData = null;
if (isset($_GET["edit"])) {
    $idEdit = (int)$_GET["edit"];
    $stmt = mysqli_prepare($koneksi, "SELECT id,nip,nama_guru,jenis_kelamin,jabatan,pendidikan,foto FROM tb_guru WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $idEdit);
    mysqli_stmt_execute($stmt);
    $hasilEdit = mysqli_stmt_get_result($stmt);
    $editData = mysqli_fetch_assoc($hasilEdit);
    mysqli_stmt_close($stmt);
}
$daftarGuru = mysqli_query($koneksi, "SELECT id,nip,nama_guru,jenis_kelamin,jabatan,pendidikan,foto FROM tb_guru ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Guru | SDN 1 Beririjarak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f4f7fb;color:#26364a;font-family:'Segoe UI',Tahoma,sans-serif}.topbar{background:#082b59;color:#fff;padding:18px 24px}.wrap{max-width:1200px;margin:28px auto;padding:0 16px}.panel{background:#fff;border:0;border-radius:14px;box-shadow:0 3px 16px rgba(20,50,90,.07);padding:24px}.table th{background:#edf4fc;color:#294665;white-space:nowrap}.foto{width:48px;height:48px;object-fit:cover;border-radius:8px}.judul{color:#082b59;font-weight:700}
</style>
</head>
<body>
<header class="topbar"><div class="container-fluid d-flex justify-content-between align-items-center"><strong><i class="bi bi-mortarboard-fill me-2"></i>SDN 1 BERIRIJARAK — ADMIN</strong><span>Halo, <?= e($_SESSION["username"]) ?></span></div></header>
<main class="wrap">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
 <div><h1 class="h3 judul mb-1">Pengelolaan Data Guru</h1><p class="text-secondary mb-0">Tambah, lihat, ubah, dan hapus data guru.</p></div>
 <a href="../dashboard_admin.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard</a>
</div>
<?php if ($pesan): ?><div class="alert alert-<?= e($jenisPesan) ?> alert-dismissible fade show"><?= e($pesan) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<section class="panel mb-4">
<h2 class="h5 judul mb-3"><?= $editData ? "Ubah Data Guru" : "Tambah Data Guru" ?></h2>
<form method="post" enctype="multipart/form-data" class="row g-3">
<input type="hidden" name="csrf" value="<?= e($_SESSION["csrf_guru"]) ?>">
<input type="hidden" name="aksi" value="<?= $editData ? "ubah" : "simpan" ?>">
<?php if ($editData): ?><input type="hidden" name="id" value="<?= (int)$editData["id"] ?>"><?php endif; ?>
<div class="col-md-4"><label class="form-label">NIP</label><input class="form-control" name="nip" maxlength="30" value="<?= e($editData["nip"] ?? "") ?>"></div>
<div class="col-md-8"><label class="form-label">Nama Guru <span class="text-danger">*</span></label><input class="form-control" name="nama_guru" maxlength="150" required value="<?= e($editData["nama_guru"] ?? "") ?>"></div>
<div class="col-md-4"><label class="form-label">Jenis Kelamin</label><select class="form-select" name="jenis_kelamin"><option value="">-- Pilih --</option><?php foreach (["Laki-laki","Perempuan"] as $jk): ?><option value="<?= e($jk) ?>" <?= ($editData["jenis_kelamin"] ?? "") === $jk ? "selected" : "" ?>><?= e($jk) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Jabatan</label><input class="form-control" name="jabatan" maxlength="100" value="<?= e($editData["jabatan"] ?? "") ?>"></div>
<div class="col-md-4"><label class="form-label">Pendidikan</label><input class="form-control" name="pendidikan" maxlength="100" value="<?= e($editData["pendidikan"] ?? "") ?>"></div>
<div class="col-md-6"><label class="form-label">Foto (JPG/PNG/WEBP, maks. 3 MB)</label><input type="file" class="form-control" name="foto" accept=".jpg,.jpeg,.png,.webp"><?php if (!empty($editData["foto"])): ?><small class="text-muted">Foto saat ini: <?= e($editData["foto"]) ?></small><?php endif; ?></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i><?= $editData ? "Simpan Perubahan" : "Tambah Guru" ?></button><?php if ($editData): ?><a href="data_guru.php" class="btn btn-light">Batal</a><?php endif; ?></div>
</form>
</section>
<section class="panel">
<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 judul mb-0">Daftar Guru</h2><span class="badge text-bg-primary"><?= mysqli_num_rows($daftarGuru) ?> data</span></div>
<div class="table-responsive"><table class="table table-hover align-middle">
<thead><tr><th>No.</th><th>Foto</th><th>NIP</th><th>Nama Guru</th><th>Jenis Kelamin</th><th>Jabatan</th><th>Pendidikan</th><th>Aksi</th></tr></thead><tbody>
<?php if (mysqli_num_rows($daftarGuru) === 0): ?><tr><td colspan="8" class="text-center text-muted py-4">Belum ada data guru.</td></tr><?php else: $no=1; while ($g=mysqli_fetch_assoc($daftarGuru)): ?>
<tr><td><?= $no++ ?></td><td><?php if ($g["foto"]): ?><img class="foto" src="../<?= e($g["foto"]) ?>" alt="Foto guru"><?php else: ?><span class="text-muted">—</span><?php endif; ?></td><td><?= e($g["nip"]) ?></td><td class="fw-semibold"><?= e($g["nama_guru"]) ?></td><td><?= e($g["jenis_kelamin"]) ?></td><td><?= e($g["jabatan"]) ?></td><td><?= e($g["pendidikan"]) ?></td><td class="text-nowrap"><a class="btn btn-sm btn-warning" href="data_guru.php?edit=<?= (int)$g["id"] ?>"><i class="bi bi-pencil"></i> Edit</a> <form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data guru ini?')"><input type="hidden" name="csrf" value="<?= e($_SESSION["csrf_guru"]) ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$g["id"] ?>"><button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button></form></td></tr>
<?php endwhile; endif; ?>
</tbody></table></div>
</section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
