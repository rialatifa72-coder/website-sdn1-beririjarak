<?php
session_start();
if (!isset($_SESSION["username"])) { header("Location: ../login.php"); exit; }
require_once __DIR__ . "/../koneksi.php";
if (!$koneksi) die("Koneksi database gagal. Periksa koneksi.php.");

function e($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
// Bersihkan spasi biasa, tab, baris baru, dan spasi khusus (NBSP) dari hasil salin Excel.
// Nilai placeholder "(tidak diisi)" juga dianggap kosong. Angka 0 tetap dianggap data sah.
function normalisasiNilai($v) {
    $v = (string)($v ?? "");
    $v = str_replace(["\\xC2\\xA0", "\\xE2\\x80\\x8B", "\\xEF\\xBB\\xBF"], " ", $v);
    $v = trim(preg_replace('/\\s+/u', ' ', $v));
    if ($v === "" || mb_strtolower($v, "UTF-8") === "(tidak diisi)") return "";
    return $v;
}
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

    ["hp", "HP", "text"],

    ["penerima_kps", "Penerima KPS", "text"],

    ["nama_ayah", "Nama Ayah", "text"],
    ["tahun_lahir_ayah", "Tahun Lahir Ayah", "text"],
    ["pendidikan_ayah", "Pendidikan Ayah", "text"],
    ["pekerjaan_ayah", "Pekerjaan Ayah", "text"],
    ["penghasilan_ayah", "Penghasilan Ayah", "text"],
    ["nama_ibu", "Nama Ibu", "text"],
    ["tahun_lahir_ibu", "Tahun Lahir Ibu", "text"],
    ["pendidikan_ibu", "Pendidikan Ibu", "text"],
    ["pekerjaan_ibu", "Pekerjaan Ibu", "text"],
    ["penghasilan_ibu", "Penghasilan Ibu", "text"],
    ["kelas", "Rombel Saat Ini / Kelas", "text"],

    ["penerima_kip", "Penerima KIP", "text"],

    ["no_registrasi_akta_lahir", "Nomor Registrasi Akta Lahir", "text"],

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
        if ($aksi === "importir") {
            if (!isset($_FILES["file_data_siswa"]) || $_FILES["file_data_siswa"]["error"] !== UPLOAD_ERR_OK) {
                throw new RuntimeException("Pilih file daftar peserta didik TXT/CSV terlebih dahulu.");
            }
            $namaFile = $_FILES["file_data_siswa"]["name"] ?? "";
            $ekstensi = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
            if (!in_array($ekstensi, ["txt", "csv"], true)) throw new RuntimeException("Format file harus TXT atau CSV hasil ekspor tab-delimited.");
            $handle = fopen($_FILES["file_data_siswa"]["tmp_name"], "r");
            if (!$handle) throw new RuntimeException("File tidak dapat dibaca.");
            // Indeks kolom mengikuti format Daftar Peserta Didik yang diberikan.
            // Seluruh kolom NIK dan kolom yang diminta dihilangkan tidak dipetakan.
            $petaKolom = [
                "nama_siswa"=>1, "nis"=>2, "jenis_kelamin"=>3, "nisn"=>4,
                "tempat_lahir"=>5, "tanggal_lahir"=>6, "agama"=>8, "alamat"=>9,
                "rt"=>10, "rw"=>11, "dusun"=>12, "kelurahan"=>13, "kecamatan"=>14,
                "kode_pos"=>15, "jenis_tinggal"=>16, "alat_transportasi"=>17, "hp"=>19,
                "penerima_kps"=>22,
                "nama_ayah"=>24, "tahun_lahir_ayah"=>25, "pendidikan_ayah"=>26,
                "pekerjaan_ayah"=>27, "penghasilan_ayah"=>28,
                "nama_ibu"=>30, "tahun_lahir_ibu"=>31, "pendidikan_ibu"=>32,
                "pekerjaan_ibu"=>33, "penghasilan_ibu"=>34,
                "kelas"=>42, "penerima_kip"=>45, "no_registrasi_akta_lahir"=>49,
                "layak_pip"=>53, "alasan_layak_pip"=>54, "kebutuhan_khusus"=>55,
                "sekolah_asal"=>56, "anak_ke"=>57, "lintang"=>58, "bujur"=>59,
                "no_kk"=>60, "berat_badan"=>61, "tinggi_badan"=>62,
                "lingkar_kepala"=>63, "jumlah_saudara_kandung"=>64,
                "jarak_rumah_ke_sekolah"=>65
            ];
            $barisKe = 0; $jumlahMasuk = 0; $jumlahLewat = 0;
            mysqli_begin_transaction($koneksi);
            try {
                while (($row = fgetcsv($handle, 0, "\t", '"', "\\")) !== false) {
                    if ($barisKe++ < 6 || !is_array($row)) continue;
                    $nama = normalisasiNilai($row[1] ?? "");
                    if ($nama === "") { $jumlahLewat++; continue; }
                    $dataIsi = [];
                    foreach ($petaKolom as $key=>$index) {
                        $v = normalisasiNilai($row[$index] ?? "");
                        if ($key === "jenis_kelamin") {
                            if ($v === "L") $v = "Laki-laki";
                            elseif ($v === "P") $v = "Perempuan";
                            elseif (!in_array($v, ["Laki-laki", "Perempuan"], true)) $v = "";
                        }
                        if ($key === "tanggal_lahir" && $v !== "" && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $v)) $v = "";
                        if ($v !== "") $dataIsi[$key] = $v;
                    }
                    $nisCek = $dataIsi["nis"] ?? "";
                    $nisnCek = $dataIsi["nisn"] ?? "";
                    $cek = mysqli_prepare($koneksi, "SELECT id FROM tb_siswa WHERE (nis <> '' AND nis = ?) OR (nisn <> '' AND nisn = ?) LIMIT 1");
                    mysqli_stmt_bind_param($cek, "ss", $nisCek, $nisnCek);
                    mysqli_stmt_execute($cek);
                    $sudahAda = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
                    mysqli_stmt_close($cek);
                    if ($sudahAda) { $jumlahLewat++; continue; }
                    $columns = implode(",", array_keys($dataIsi));
                    $marks = implode(",", array_fill(0, count($dataIsi), "?"));
                    $values = array_values($dataIsi);
                    $stmtImport = mysqli_prepare($koneksi, "INSERT INTO tb_siswa ($columns) VALUES ($marks)");
                    if (!$stmtImport) throw new RuntimeException("Gagal menyiapkan impor: " . mysqli_error($koneksi));
                    bindDynamic($stmtImport, str_repeat("s", count($values)), $values);
                    if (!mysqli_stmt_execute($stmtImport)) {
                        $errImport = mysqli_stmt_error($stmtImport);
                        mysqli_stmt_close($stmtImport);
                        throw new RuntimeException("Impor dihentikan: " . $errImport);
                    }
                    mysqli_stmt_close($stmtImport);
                    $jumlahMasuk++;
                }
                fclose($handle);
                mysqli_commit($koneksi);
                $pesan = "Impor selesai: " . $jumlahMasuk . " siswa ditambahkan, " . $jumlahLewat . " baris kosong/duplikat dilewati. Kolom kosong tidak disimpan.";
            } catch (Throwable $errImport) {
                mysqli_rollback($koneksi);
                if (is_resource($handle)) fclose($handle);
                throw $errImport;
            }
        } elseif ($aksi === "hapus") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id < 1) throw new RuntimeException("ID siswa tidak valid.");
            $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_siswa WHERE id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) < 1) throw new RuntimeException("Data siswa tidak ditemukan.");
            mysqli_stmt_close($stmt);
            $pesan = "Data siswa berhasil dihapus.";
        } elseif ($aksi === "simpan" || $aksi === "ubah") {
            // Hanya kolom yang memiliki nilai yang dikirim ke database.
            // Kolom kosong dilewati; nomor NIK tidak termasuk daftar field CRUD.
            $dataIsi = [];
            foreach ($fieldKeys as $key) {
                $v = normalisasiNilai($_POST[$key] ?? "");
                if ($key === "nama_siswa" && $v === "") throw new RuntimeException("Nama siswa wajib diisi.");
                if ($key === "jenis_kelamin" && !in_array($v, ["", "L", "P", "Laki-laki", "Perempuan"], true)) throw new RuntimeException("Jenis kelamin tidak valid.");
                if ($key === "jenis_kelamin" && $v === "L") $v = "Laki-laki";
                if ($key === "jenis_kelamin" && $v === "P") $v = "Perempuan";
                if ($key === "status" && !in_array($v, ["Aktif", "Lulus", "Pindah"], true)) throw new RuntimeException("Status tidak valid.");
                if ($key === "tanggal_lahir" && $v !== "" && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $v)) throw new RuntimeException("Tanggal lahir tidak valid.");
                if ($v !== "") $dataIsi[$key] = $v;
            }
            if ($aksi === "simpan") {
                $columns = implode(",", array_keys($dataIsi));
                $marks = implode(",", array_fill(0, count($dataIsi), "?"));
                $values = array_values($dataIsi);
                $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_siswa ($columns) VALUES ($marks)");
            } else {
                $id = (int)($_POST["id"] ?? 0);
                if ($id < 1) throw new RuntimeException("ID siswa tidak valid.");
                if (!$dataIsi) throw new RuntimeException("Tidak ada perubahan: semua kolom kosong.");
                $sets = implode(",", array_map(fn($k) => "$k=?", array_keys($dataIsi)));
                $values = array_values($dataIsi);
                $values[] = $id;
                $stmt = mysqli_prepare($koneksi, "UPDATE tb_siswa SET $sets WHERE id=?");
            }
            if (!$stmt) throw new RuntimeException("Gagal menyiapkan perintah database: " . mysqli_error($koneksi));
            $types = str_repeat("s", count($values));
            if ($aksi === "ubah") $types = str_repeat("s", count($values)-1) . "i";
            bindDynamic($stmt, $types, $values);
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException("Gagal menyimpan data: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            $pesan = $aksi === "simpan" ? "Data siswa berhasil ditambahkan. Kolom kosong tidak disimpan." : "Data siswa berhasil diperbarui. Kolom yang dikosongkan tidak menimpa data lama.";
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
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_siswa WHERE nama_siswa LIKE ? OR nis LIKE ? OR nisn LIKE ? OR kelas LIKE ? ORDER BY nama_siswa ASC");
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $daftar = mysqli_stmt_get_result($stmt);
} else {
    $daftar = mysqli_query($koneksi, "SELECT * FROM tb_siswa ORDER BY nama_siswa ASC");
}
if (!$daftar) die("Gagal mengambil data siswa: " . e(mysqli_error($koneksi)));
$grup = [
 "Identitas Peserta Didik" => ["nis","nama_siswa","jenis_kelamin","nisn","tempat_lahir","tanggal_lahir","agama"],
 "Alamat dan Kontak" => ["alamat","rt","rw","dusun","kelurahan","kecamatan","kode_pos","jenis_tinggal","alat_transportasi","hp",],
 "Data Administrasi" => ["penerima_kps","kelas","tahun_ajaran","status"],
 "Data Ayah" => ["nama_ayah","tahun_lahir_ayah","pendidikan_ayah","pekerjaan_ayah","penghasilan_ayah"],
 "Data Ibu" => ["nama_ibu","tahun_lahir_ibu","pendidikan_ibu","pekerjaan_ibu","penghasilan_ibu"],
 "Data Bantuan dan Lainnya" => ["penerima_kip","no_registrasi_akta_lahir","layak_pip","alasan_layak_pip","kebutuhan_khusus","sekolah_asal","anak_ke","lintang","bujur","no_kk","berat_badan","tinggi_badan","lingkar_kepala","jumlah_saudara_kandung","jarak_rumah_ke_sekolah"]
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
body{background:#f3f7fc;color:#26364a;font-family:Segoe UI,Tahoma,sans-serif}.topbar{background:#082b59;color:#fff;padding:12px 24px}.logo-admin{width:52px;height:52px;object-fit:contain;background:#fff;border-radius:8px;padding:3px}.wrap{max-width:1450px;margin:28px auto;padding:0 16px}.panel{background:#fff;border:0;border-radius:14px;box-shadow:0 3px 16px rgba(20,50,90,.07);padding:24px}.judul{color:#082b59;font-weight:700}.group-title{color:#174b82;font-weight:700;border-bottom:1px solid #e5edf6;padding-bottom:9px;margin:22px 0 15px}.table th{background:#edf4fc;white-space:nowrap}.table td{vertical-align:middle}.table-siswa{min-width:2200px}.table-siswa td{max-width:260px;white-space:normal;overflow-wrap:anywhere}
</style></head><body>
<header class="topbar"><div class="container-fluid d-flex justify-content-between"><div class="d-flex align-items-center gap-3"><img src="../logo.jpeg" alt="Logo SDN 1 Beririjarak" class="logo-admin"><strong>SDN 1 BERIRIJARAK — ADMIN</strong></div><span>Halo, <?= e($_SESSION["username"]) ?></span></div></header>
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
<?php elseif ($type === "select" && $key === "jenis_kelamin"): ?><select class="form-select" name="<?= e($key) ?>"><option value="">-- Pilih --</option><?php foreach (["L"=>"Laki-laki","P"=>"Perempuan"] as $optValue=>$optLabel): ?><option value="<?= e($optValue) ?>" <?= ($value === $optValue || $value === $optLabel) ? "selected" : "" ?>><?= e($optLabel) ?></option><?php endforeach; ?></select>
<?php elseif ($type === "select" && $key === "status"): ?><select class="form-select" name="<?= e($key) ?>"><?php foreach (["Aktif","Lulus","Pindah"] as $opt): ?><option <?= ($value ?: "Aktif") === $opt ? "selected" : "" ?>><?= e($opt) ?></option><?php endforeach; ?></select>
<?php else: ?><input class="form-control" type="<?= e($type) ?>" name="<?= e($key) ?>" <?= $key === "nama_siswa" ? "required" : "" ?> value="<?= e($value) ?>"><?php endif; ?>
</div><?php endforeach; ?></div><?php endforeach; ?>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i><?= $editData ? "Simpan Perubahan" : "Tambah Siswa" ?></button><?php if ($editData): ?><a href="data_siswa.php" class="btn btn-light">Batal</a><?php endif; ?></div>
</form></section>
<section class="panel mb-4">
<h2 class="h5 judul">Impor Daftar Peserta Didik</h2>
<p class="small text-secondary mb-3">Unggah file TXT/CSV hasil ekspor Dapodik (dipisahkan tab). Data yang kosong, baris kosong, dan siswa yang sudah terdaftar akan dilewati. Kolom NIK serta kolom yang tidak digunakan tidak diimpor.</p>
<form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
<input type="hidden" name="csrf" value="<?= e($_SESSION[$csrfName]) ?>">
<input type="hidden" name="aksi" value="importir">
<div class="col-md-8"><label class="form-label" for="file_data_siswa">File daftar peserta didik (.txt atau .csv)</label><input class="form-control" type="file" id="file_data_siswa" name="file_data_siswa" accept=".txt,.csv" required></div>
<div class="col-md-4"><button class="btn btn-success w-100" onclick="return confirm('Impor data dari file ini ke database siswa? Data kosong dan data ganda akan dilewati.')"><i class="bi bi-upload me-1"></i>Impor Data Siswa</button></div>
</form>
</section>
<section class="panel"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 judul mb-0">Daftar Siswa</h2><form class="d-flex gap-2" method="get"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama, NIS, NISN, kelas"><button class="btn btn-outline-primary"><i class="bi bi-search"></i></button></form></div>
<div class="table-responsive"><table class="table table-hover table-bordered align-middle table-siswa"><thead><tr><th>No</th><?php foreach ($fields as $f): ?><th><?= e($f[1]) ?></th><?php endforeach; ?><th>Aksi</th></tr></thead><tbody>
<?php if (mysqli_num_rows($daftar) === 0): ?><tr><td colspan="<?= count($fields) + 2 ?>" class="text-center text-muted py-4">Belum ada data siswa.</td></tr><?php else: $no=1; while ($s=mysqli_fetch_assoc($daftar)): ?><tr><td><?= $no++ ?></td><?php foreach ($fields as $f): $kolom = $f[0]; ?><td><?= e($s[$kolom] ?? "") ?></td><?php endforeach; ?><td class="text-nowrap"><a class="btn btn-sm btn-warning" href="data_siswa.php?edit=<?= (int)$s["id"] ?>"><i class="bi bi-pencil"></i> Ubah</a> <form method="post" class="d-inline" onsubmit="return confirm('Hapus biodata siswa ini?')"><input type="hidden" name="csrf" value="<?= e($_SESSION[$csrfName]) ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$s["id"] ?>"><button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button></form></td></tr><?php endwhile; endif; ?>
</tbody></table></div></section>
<p class="small text-muted mt-3"><i class="bi bi-shield-lock me-1"></i>Data pribadi siswa, orang tua, dan wali disimpan pada database lokal sekolah. Jangan unggah file data peserta didik ke repositori publik.</p>
</main></body></html>