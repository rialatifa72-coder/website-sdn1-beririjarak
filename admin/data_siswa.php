<?php
session_start();
if (!isset($_SESSION["username"])) { header("Location: ../login.php"); exit; }
require_once __DIR__ . "/../koneksi.php";
if (!$koneksi) die("Koneksi database gagal. Periksa koneksi.php.");

function e($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
function bindDynamic($stmt, $types, &$values) {
    $args = [$types];
    foreach ($values as $i => &$value) $args[] = &$value;
    return call_user_func_array([$stmt, "bind_param"], $args);
}
$fields = [
    ["nis", "NIPD / NIS", "text"],
    ["nama_siswa", "Nama Lengkap", "text"],
    ["jenis_kelamin", "Jenis Kelamin", "select"],
    ["nisn", "NISN", "text"],
    ["tempat_lahir", "Tempat Lahir", "text"],
    ["tanggal_lahir", "Tanggal Lahir", "date"],
    ["nik", "NIK", "text"],
    ["agama", "Agama", "text"],
    ["alamat", "Alamat", "textarea"],
    ["rt", "RT", "text"],
    ["rw", "RW", "text"],
    ["dusun", "Dusun", "text"],
    ["kelurahan", "Kelurahan / Desa", "text"],
    ["kecamatan", "Kecamatan", "text"],
    ["kode_pos", "Kode Pos", "text"],
    ["jenis_tinggal", "Jenis Tinggal", "text"],
    ["alat_transportasi", "Alat Transportasi", "text"],
    ["telepon", "Telepon", "text"],
    ["hp", "HP", "text"],
    ["email", "E-Mail", "email"],
    ["skhun", "SKHUN", "text"],
    ["penerima_kps", "Penerima KPS", "text"],
    ["no_kps", "Nomor KPS", "text"],
    ["nama_ayah", "Nama Ayah", "text"],
    ["tahun_lahir_ayah", "Tahun Lahir Ayah", "text"],
    ["pendidikan_ayah", "Pendidikan Ayah", "text"],
    ["pekerjaan_ayah", "Pekerjaan Ayah", "text"],
    ["penghasilan_ayah", "Penghasilan Ayah", "text"],
    ["nik_ayah", "NIK Ayah", "text"],
    ["nama_ibu", "Nama Ibu", "text"],
    ["tahun_lahir_ibu", "Tahun Lahir Ibu", "text"],
    ["pendidikan_ibu", "Pendidikan Ibu", "text"],
    ["pekerjaan_ibu", "Pekerjaan Ibu", "text"],
    ["penghasilan_ibu", "Penghasilan Ibu", "text"],
    ["nik_ibu", "NIK Ibu", "text"],
    ["nama_wali", "Nama Wali", "text"],
    ["tahun_lahir_wali", "Tahun Lahir Wali", "text"],
    ["pendidikan_wali", "Pendidikan Wali", "text"],
    ["pekerjaan_wali", "Pekerjaan Wali", "text"],
    ["penghasilan_wali", "Penghasilan Wali", "text"],
    ["nik_wali", "NIK Wali", "text"],
    ["kelas", "Rombel Saat Ini / Kelas", "text"],
    ["no_peserta_ujian_nasional", "Nomor Peserta Ujian Nasional", "text"],
    ["no_seri_ijazah", "Nomor Seri Ijazah", "text"],
    ["penerima_kip", "Penerima KIP", "text"],
    ["nomor_kip", "Nomor KIP", "text"],
    ["nama_di_kip", "Nama di KIP", "text"],
    ["nomor_kks", "Nomor KKS", "text"],
    ["no_registrasi_akta_lahir", "Nomor Registrasi Akta Lahir", "text"],
    ["bank", "Bank", "text"],
    ["nomor_rekening_bank", "Nomor Rekening Bank", "text"],
    ["rekening_atas_nama", "Rekening Atas Nama", "text"],
    ["layak_pip", "Layak PIP", "text"],
    ["alasan_layak_pip", "Alasan Layak PIP", "textarea"],
    ["kebutuhan_khusus", "Kebutuhan Khusus", "text"],
    ["sekolah_asal", "Sekolah Asal", "text"],
    ["anak_ke", "Anak Ke-berapa", "text"],
    ["lintang", "Lintang", "text"],
    ["bujur", "Bujur", "text"],
    ["no_kk", "Nomor KK", "text"],
    ["berat_badan", "Berat Badan", "text"],
    ["tinggi_badan", "Tinggi Badan", "text"],
    ["lingkar_kepala", "Lingkar Kepala", "text"],
    ["jumlah_saudara_kandung", "Jumlah Saudara Kandung", "text"],
    ["jarak_rumah_ke_sekolah", "Jarak Rumah ke Sekolah (KM)", "text"],
    ["tahun_ajaran", "Tahun Ajaran", "text"],
    ["status", "Status Siswa", "select"],
];
$fieldKeys = array_column($fields, 0);
$fieldLabels = array_column($fields, 1);
$csrfName = "csrf_siswa_full";
if (empty($_SESSION[$csrfName])) $_SESSION[$csrfName] = bin2hex(random_bytes(32));
$pesan = ""; $jenisPesan = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (!hash_equals($_SESSION[$csrfName], $_POST["csrf"] ?? "")) throw new RuntimeException("Sesi formulir tidak valid. Muat ulang halaman.");
        $aksi = $_POST["aksi"] ?? "";
        if ($aksi === "hapus") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id < 1) throw new RuntimeException("ID siswa tidak valid.");
            $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_siswa WHERE id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) < 1) throw new RuntimeException("Data siswa tidak ditemukan.");
            mysqli_stmt_close($stmt);
            $pesan = "Data siswa berhasil dihapus.";
        } elseif ($aksi === "simpan" || $aksi === "ubah") {
            $values = [];
            foreach ($fieldKeys as $key) {
                $v = trim((string)($_POST[$key] ?? ""));
                if ($key === "nama_siswa" && $v === "") throw new RuntimeException("Nama siswa wajib diisi.");
                if ($key === "jenis_kelamin" && !in_array($v, ["", "Laki-laki", "Perempuan"], true)) throw new RuntimeException("Jenis kelamin tidak valid.");
                if ($key === "status" && !in_array($v, ["Aktif", "Lulus", "Pindah"], true)) throw new RuntimeException("Status tidak valid.");
                if ($key === "tanggal_lahir" && $v !== "" && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $v)) throw new RuntimeException("Tanggal lahir tidak valid.");
                $values[] = $v === "" ? null : $v;
            }
            if ($aksi === "simpan") {
                $columns = implode(",", $fieldKeys);
                $marks = implode(",", array_fill(0, count($fieldKeys), "?"));
                $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_siswa ($columns) VALUES ($marks)");
            } else {
                $id = (int)($_POST["id"] ?? 0);
                if ($id < 1) throw new RuntimeException("ID siswa tidak valid.");
                $sets = implode(",", array_map(fn($k) => "$k=?", $fieldKeys));
                $stmt = mysqli_prepare($koneksi, "UPDATE tb_siswa SET $sets WHERE id=?");
                $values[] = $id;
            }
            if (!$stmt) throw new RuntimeException("Gagal menyiapkan perintah database: " . mysqli_error($koneksi));
            $types = str_repeat("s", count($values));
            if ($aksi === "ubah") $types = str_repeat("s", count($values)-1) . "i";
            bindDynamic($stmt, $types, $values);
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Gagal menyimpan data: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            $pesan = $aksi === "simpan" ? "Data siswa berhasil ditambahkan." : "Data siswa berhasil diperbarui.";
        }
    } catch (Throwable $err) { $pesan = $err->getMessage(); $jenisPesan = "danger"; }
}

$editData = null;
if (isset($_GET["edit"])) {
    $idEdit = (int)$_GET["edit"];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_siswa WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $idEdit);
    mysqli_stmt_execute($stmt);
    $editData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$editData) { $pesan = "Data siswa tidak ditemukan."; $jenisPesan = "warning"; }
}
$q = trim($_GET["q"] ?? "");
if ($q !== "") {
    $like = "%".$q."%";
    $stmt = mysqli_prepare($koneksi, "SELECT id,nis,nisn,nama_siswa,jenis_kelamin,kelas,status FROM tb_siswa WHERE nama_siswa LIKE ? OR nis LIKE ? OR nisn LIKE ? OR kelas LIKE ? ORDER BY nama_siswa ASC");
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $daftar = mysqli_stmt_get_result($stmt);
} else {
    $daftar = mysqli_query($koneksi, "SELECT id,nis,nisn,nama_siswa,jenis_kelamin,kelas,status FROM tb_siswa ORDER BY nama_siswa ASC");
}
if (!$daftar) die("Gagal mengambil data siswa: " . e(mysqli_error($koneksi)));
$grup = [
 "Identitas Peserta Didik" => ["nis","nama_siswa","jenis_kelamin","nisn","tempat_lahir","tanggal_lahir","nik","agama"],
 "Alamat dan Kontak" => ["alamat","rt","rw","dusun","kelurahan","kecamatan","kode_pos","jenis_tinggal","alat_transportasi","telepon","hp","email"],
 "Data Administrasi" => ["skhun","penerima_kps","no_kps","kelas","no_peserta_ujian_nasional","no_seri_ijazah","tahun_ajaran","status"],
 "Data Ayah" => ["nama_ayah","tahun_lahir_ayah","pendidikan_ayah","pekerjaan_ayah","penghasilan_ayah","nik_ayah"],
 "Data Ibu" => ["nama_ibu","tahun_lahir_ibu","pendidikan_ibu","pekerjaan_ibu","penghasilan_ibu","nik_ibu"],
 "Data Wali" => ["nama_wali","tahun_lahir_wali","pendidikan_wali","pekerjaan_wali","penghasilan_wali","nik_wali"],
 "Data Bantuan dan Lainnya" => ["penerima_kip","nomor_kip","nama_di_kip","nomor_kks","no_registrasi_akta_lahir","bank","nomor_rekening_bank","rekening_atas_nama","layak_pip","alasan_layak_pip","kebutuhan_khusus","sekolah_asal","anak_ke","lintang","bujur","no_kk","berat_badan","tinggi_badan","lingkar_kepala","jumlah_saudara_kandung","jarak_rumah_ke_sekolah"]
];
$labelMap = [];
foreach ($fields as $f) $labelMap[$f[0]] = [$f[1], $f[2]];
?>
<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CRUD Data Siswa | SDN 1 Beririjarak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f3f7fc;color:#26364a;font-family:Segoe UI,Tahoma,sans-serif}.topbar{background:#082b59;color:#fff;padding:17px 24px}.wrap{max-width:1450px;margin:28px auto;padding:0 16px}.panel{background:#fff;border:0;border-radius:14px;box-shadow:0 3px 16px rgba(20,50,90,.07);padding:24px}.judul{color:#082b59;font-weight:700}.group-title{color:#174b82;font-weight:700;border-bottom:1px solid #e5edf6;padding-bottom:9px;margin:22px 0 15px}.table th{background:#edf4fc;white-space:nowrap}.table td{vertical-align:middle}
</style></head><body>
<header class="topbar"><div class="container-fluid d-flex justify-content-between"><strong><i class="bi bi-mortarboard-fill me-2"></i>SDN 1 BERIRIJARAK — ADMIN</strong><span>Halo, <?= e($_SESSION["username"]) ?></span></div></header>
<main class="wrap">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 judul mb-1">CRUD Data Siswa</h1><p class="text-secondary mb-0">Kelola seluruh biodata peserta didik, termasuk data orang tua dan wali.</p></div><a href="../dashboard_admin.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Dashboard</a></div>
<?php if ($pesan): ?><div class="alert alert-<?= e($jenisPesan) ?>"><?= e($pesan) ?></div><?php endif; ?>
<section class="panel mb-4">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><h2 class="h5 judul mb-0"><?= $editData ? "Ubah Biodata Siswa" : "Tambah Biodata Siswa" ?></h2><span class="text-muted small">Kolom bertanda * wajib diisi</span></div>
<form method="post">
<input type="hidden" name="csrf" value="<?= e($_SESSION[$csrfName]) ?>"><input type="hidden" name="aksi" value="<?= $editData ? "ubah" : "simpan" ?>">
<?php if ($editData): ?><input type="hidden" name="id" value="<?= (int)$editData["id"] ?>"><?php endif; ?>
<?php foreach ($grup as $judulGrup => $keys): ?><h3 class="h6 group-title"><?= e($judulGrup) ?></h3><div class="row g-3">
<?php foreach ($keys as $key): if (!isset($labelMap[$key])) continue; [$label,$type] = $labelMap[$key]; $value = $editData[$key] ?? ""; ?>
<div class="<?= $type === "textarea" ? "col-md-12" : "col-md-4" ?>"><label class="form-label"><?= e($label) ?><?= $key === "nama_siswa" ? ' <span class="text-danger">*</span>' : "" ?></label>
<?php if ($type === "textarea"): ?><textarea class="form-control" name="<?= e($key) ?>" rows="2"><?= e($value) ?></textarea>
<?php elseif ($type === "select" && $key === "jenis_kelamin"): ?><select class="form-select" name="<?= e($key) ?>"><option value="">-- Pilih --</option><?php foreach (["Laki-laki","Perempuan"] as $opt): ?><option <?= $value === $opt ? "selected" : "" ?>><?= e($opt) ?></option><?php endforeach; ?></select>
<?php elseif ($type === "select" && $key === "status"): ?><select class="form-select" name="<?= e($key) ?>"><?php foreach (["Aktif","Lulus","Pindah"] as $opt): ?><option <?= ($value ?: "Aktif") === $opt ? "selected" : "" ?>><?= e($opt) ?></option><?php endforeach; ?></select>
<?php else: ?><input class="form-control" type="<?= e($type) ?>" name="<?= e($key) ?>" <?= $key === "nama_siswa" ? "required" : "" ?> value="<?= e($value) ?>"><?php endif; ?>
</div><?php endforeach; ?></div><?php endforeach; ?>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i><?= $editData ? "Simpan Perubahan" : "Tambah Siswa" ?></button><?php if ($editData): ?><a href="data_siswa.php" class="btn btn-light">Batal</a><?php endif; ?></div>
</form></section>
<section class="panel"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 judul mb-0">Daftar Siswa</h2><form class="d-flex gap-2" method="get"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama, NIS, NISN, kelas"><button class="btn btn-outline-primary"><i class="bi bi-search"></i></button></form></div>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>No</th><th>NIS</th><th>NISN</th><th>Nama Siswa</th><th>JK</th><th>Kelas</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if (mysqli_num_rows($daftar) === 0): ?><tr><td colspan="8" class="text-center text-muted py-4">Belum ada data siswa.</td></tr><?php else: $no=1; while ($s=mysqli_fetch_assoc($daftar)): ?><tr><td><?= $no++ ?></td><td><?= e($s["nis"]) ?></td><td><?= e($s["nisn"]) ?></td><td class="fw-semibold"><?= e($s["nama_siswa"]) ?></td><td><?= e($s["jenis_kelamin"]) ?></td><td><?= e($s["kelas"]) ?></td><td><?= e($s["status"]) ?></td><td class="text-nowrap"><a class="btn btn-sm btn-warning" href="data_siswa.php?edit=<?= (int)$s["id"] ?>"><i class="bi bi-pencil"></i> Ubah</a> <form method="post" class="d-inline" onsubmit="return confirm('Hapus biodata siswa ini?')"><input type="hidden" name="csrf" value="<?= e($_SESSION[$csrfName]) ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$s["id"] ?>"><button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button></form></td></tr><?php endwhile; endif; ?>
</tbody></table></div></section>
<p class="small text-muted mt-3"><i class="bi bi-shield-lock me-1"></i>Data pribadi siswa, orang tua, dan wali disimpan pada database lokal sekolah. Jangan unggah file data peserta didik ke repositori publik.</p>
</main></body></html>