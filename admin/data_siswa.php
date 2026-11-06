<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit;
}
require_once __DIR__ . "/../koneksi.php";
if (!$koneksi) die("Koneksi database gagal. Periksa koneksi.php.");

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
if (empty($_SESSION["csrf_siswa"])) {
    $_SESSION["csrf_siswa"] = bin2hex(random_bytes(32));
}
$pesan = "";
$jenisPesan = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (!hash_equals($_SESSION["csrf_siswa"], $_POST["csrf"] ?? "")) {
            throw new RuntimeException("Sesi formulir tidak valid. Muat ulang halaman.");
        }
        $aksi = $_POST["aksi"] ?? "";
        if ($aksi === "hapus") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id < 1) throw new RuntimeException("Data siswa tidak valid.");
            $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_siswa WHERE id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Data siswa gagal dihapus.");
            if (mysqli_stmt_affected_rows($stmt) < 1) throw new RuntimeException("Data siswa tidak ditemukan.");
            mysqli_stmt_close($stmt);
            $pesan = "Data siswa berhasil dihapus.";
        } elseif ($aksi === "simpan" || $aksi === "ubah") {
            $id = (int)($_POST["id"] ?? 0);
            $nis = trim($_POST["nis"] ?? "");
            $nisn = trim($_POST["nisn"] ?? "");
            $nama = trim($_POST["nama_siswa"] ?? "");
            $jk = $_POST["jenis_kelamin"] ?? "";
            $tempat = trim($_POST["tempat_lahir"] ?? "");
            $tanggal = trim($_POST["tanggal_lahir"] ?? "");
            $alamat = trim($_POST["alamat"] ?? "");
            $kelas = trim($_POST["kelas"] ?? "");
            $tahun = trim($_POST["tahun_ajaran"] ?? "");
            $status = $_POST["status"] ?? "Aktif";

            if ($nama === "") throw new RuntimeException("Nama siswa wajib diisi.");
            if (!in_array($jk, ["", "Laki-laki", "Perempuan"], true)) throw new RuntimeException("Jenis kelamin tidak valid.");
            if (!in_array($status, ["Aktif", "Lulus", "Pindah"], true)) throw new RuntimeException("Status siswa tidak valid.");
            if ($tanggal !== "" && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $tanggal)) throw new RuntimeException("Format tanggal lahir tidak valid.");
            $tanggal = $tanggal === "" ? null : $tanggal;

            if ($aksi === "simpan") {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_siswa (nis,nisn,nama_siswa,jenis_kelamin,tempat_lahir,tanggal_lahir,alamat,kelas,tahun_ajaran,status) VALUES (?,?,?,NULLIF(?,''),?,?,?, ?,?,?)");
                mysqli_stmt_bind_param($stmt, "ssssssssss", $nis, $nisn, $nama, $jk, $tempat, $tanggal, $alamat, $kelas, $tahun, $status);
            } else {
                if ($id < 1) throw new RuntimeException("ID siswa tidak valid.");
                $stmt = mysqli_prepare($koneksi, "UPDATE tb_siswa SET nis=?,nisn=?,nama_siswa=?,jenis_kelamin=NULLIF(?,''),tempat_lahir=?,tanggal_lahir=?,alamat=?,kelas=?,tahun_ajaran=?,status=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssssssssi", $nis, $nisn, $nama, $jk, $tempat, $tanggal, $alamat, $kelas, $tahun, $status, $id);
            }
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Data gagal disimpan: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            $pesan = $aksi === "simpan" ? "Data siswa berhasil ditambahkan." : "Data siswa berhasil diperbarui.";
        }
    } catch (Throwable $err) {
        $pesan = $err->getMessage();
        $jenisPesan = "danger";
    }
}

$editData = null;
if (isset($_GET["edit"])) {
    $idEdit = (int)$_GET["edit"];
    $stmt = mysqli_prepare($koneksi, "SELECT id,nis,nisn,nama_siswa,jenis_kelamin,tempat_lahir,tanggal_lahir,alamat,kelas,tahun_ajaran,status FROM tb_siswa WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $idEdit);
    mysqli_stmt_execute($stmt);
    $editData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$editData) {
        $pesan = "Data siswa yang akan diubah tidak ditemukan.";
        $jenisPesan = "warning";
    }
}
$daftarSiswa = mysqli_query($koneksi, "SELECT id,nis,nisn,nama_siswa,jenis_kelamin,tempat_lahir,tanggal_lahir,alamat,kelas,tahun_ajaran,status FROM tb_siswa ORDER BY id DESC");
if (!$daftarSiswa) die("Gagal mengambil data siswa: " . e(mysqli_error($koneksi)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Siswa | SDN 1 Beririjarak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f4f7fb;color:#26364a;font-family:'Segoe UI',Tahoma,sans-serif}.topbar{background:#082b59;color:#fff;padding:18px 24px}.wrap{max-width:1400px;margin:28px auto;padding:0 16px}.panel{background:#fff;border:0;border-radius:14px;box-shadow:0 3px 16px rgba(20,50,90,.07);padding:24px}.judul{color:#082b59;font-weight:700}.table th{background:#edf4fc;color:#294665;white-space:nowrap}.table td{vertical-align:middle}
</style>
</head>
<body>
<header class="topbar"><div class="container-fluid d-flex justify-content-between align-items-center"><strong><i class="bi bi-mortarboard-fill me-2"></i>SDN 1 BERIRIJARAK — ADMIN</strong><span>Halo, <?= e($_SESSION["username"]) ?></span></div></header>
<main class="wrap">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
 <div><h1 class="h3 judul mb-1">Pengelolaan Data Siswa</h1><p class="text-secondary mb-0">Tambah, lihat, ubah, dan hapus data peserta didik.</p></div>
 <a href="../dashboard_admin.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard</a>
</div>
<?php if ($pesan): ?><div class="alert alert-<?= e($jenisPesan) ?> alert-dismissible fade show"><?= e($pesan) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<section class="panel mb-4">
<h2 class="h5 judul mb-3"><?= $editData ? "Ubah Data Siswa" : "Tambah Data Siswa" ?></h2>
<form method="post" class="row g-3">
<input type="hidden" name="csrf" value="<?= e($_SESSION["csrf_siswa"]) ?>">
<input type="hidden" name="aksi" value="<?= $editData ? "ubah" : "simpan" ?>">
<?php if ($editData): ?><input type="hidden" name="id" value="<?= (int)$editData["id"] ?>"><?php endif; ?>
<div class="col-md-3"><label class="form-label">NIS</label><input class="form-control" name="nis" maxlength="30" value="<?= e($editData["nis"] ?? "") ?>"></div>
<div class="col-md-3"><label class="form-label">NISN</label><input class="form-control" name="nisn" maxlength="30" value="<?= e($editData["nisn"] ?? "") ?>"></div>
<div class="col-md-6"><label class="form-label">Nama Lengkap <span class="text-danger">*</span></label><input class="form-control" name="nama_siswa" maxlength="150" required value="<?= e($editData["nama_siswa"] ?? "") ?>"></div>
<div class="col-md-3"><label class="form-label">Jenis Kelamin</label><select class="form-select" name="jenis_kelamin"><option value="">-- Pilih --</option><?php foreach (["Laki-laki","Perempuan"] as $jk): ?><option value="<?= e($jk) ?>" <?= ($editData["jenis_kelamin"] ?? "") === $jk ? "selected" : "" ?>><?= e($jk) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Tempat Lahir</label><input class="form-control" name="tempat_lahir" maxlength="100" value="<?= e($editData["tempat_lahir"] ?? "") ?>"></div>
<div class="col-md-3"><label class="form-label">Tanggal Lahir</label><input type="date" class="form-control" name="tanggal_lahir" value="<?= e($editData["tanggal_lahir"] ?? "") ?>"></div>
<div class="col-md-3"><label class="form-label">Kelas</label><input class="form-control" name="kelas" maxlength="30" placeholder="Contoh: 1A" value="<?= e($editData["kelas"] ?? "") ?>"></div>
<div class="col-md-4"><label class="form-label">Tahun Ajaran</label><input class="form-control" name="tahun_ajaran" maxlength="20" placeholder="Contoh: 2026/2027" value="<?= e($editData["tahun_ajaran"] ?? "") ?>"></div>
<div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (["Aktif","Lulus","Pindah"] as $st): ?><option value="<?= e($st) ?>" <?= ($editData["status"] ?? "Aktif") === $st ? "selected" : "" ?>><?= e($st) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat" rows="1"><?= e($editData["alamat"] ?? "") ?></textarea></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i><?= $editData ? "Simpan Perubahan" : "Tambah Siswa" ?></button><?php if ($editData): ?><a href="data_siswa.php" class="btn btn-light">Batal</a><?php endif; ?></div>
</form>
</section>
<section class="panel">
<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 judul mb-0">Daftar Siswa</h2><span class="badge text-bg-primary"><?= mysqli_num_rows($daftarSiswa) ?> data</span></div>
<div class="table-responsive"><table class="table table-hover align-middle">
<thead><tr><th>No.</th><th>NIS</th><th>NISN</th><th>Nama Siswa</th><th>JK</th><th>Tempat, Tanggal Lahir</th><th>Kelas</th><th>Tahun Ajaran</th><th>Alamat</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if (mysqli_num_rows($daftarSiswa) === 0): ?><tr><td colspan="11" class="text-center text-muted py-4">Belum ada data siswa.</td></tr><?php else: $no=1; while ($s=mysqli_fetch_assoc($daftarSiswa)): ?>
<tr><td><?= $no++ ?></td><td><?= e($s["nis"]) ?></td><td><?= e($s["nisn"]) ?></td><td class="fw-semibold"><?= e($s["nama_siswa"]) ?></td><td><?= e($s["jenis_kelamin"]) ?></td><td><?= e($s["tempat_lahir"]) ?><?= $s["tanggal_lahir"] ? ", " . e(date("d-m-Y", strtotime($s["tanggal_lahir"]))) : "" ?></td><td><?= e($s["kelas"]) ?></td><td><?= e($s["tahun_ajaran"]) ?></td><td><?= e($s["alamat"]) ?></td><td><span class="badge text-bg-<?= $s["status"] === "Aktif" ? "success" : ($s["status"] === "Lulus" ? "primary" : "secondary") ?>"><?= e($s["status"]) ?></span></td><td class="text-nowrap"><a class="btn btn-sm btn-warning" href="data_siswa.php?edit=<?= (int)$s["id"] ?>"><i class="bi bi-pencil"></i> Edit</a> <form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data siswa ini?')"><input type="hidden" name="csrf" value="<?= e($_SESSION["csrf_siswa"]) ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$s["id"] ?>"><button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button></form></td></tr>
<?php endwhile; endif; ?>
</tbody></table></div>
</section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
