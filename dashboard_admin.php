<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . "/koneksi.php";

$namaAdmin = $_SESSION["username"];
$allowedPages = ["beranda", "profil", "guru", "siswa", "berita", "galeri", "media"];
$page = $_GET["page"] ?? "beranda";
if (!in_array($page, $allowedPages, true)) {
    $page = "beranda";
}

function jumlahData($koneksi, $table) {
    if (!$koneksi) return null;
    $allowed = ["tb_users", "tb_guru", "tb_siswa", "tb_berita", "tb_galeri", "tb_media"];
    if (!in_array($table, $allowed, true)) return 0;
    $result = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM " . $table);
    if (!$result) return null;
    $row = mysqli_fetch_assoc($result);
    return (int) ($row["total"] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin | SDN 1 Beririjarak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--navy:#082b59;--blue:#0d6efd;--sidebar:#174b82;--bg:#f4f7fb}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:#26364a}
.wrapper{display:flex;min-height:100vh}
#sidebar{width:250px;min-width:250px;background:var(--sidebar);color:#fff;transition:.25s}
#sidebar.collapsed{margin-left:-250px}
.sidebar-header{padding:20px 18px;background:var(--navy);font-size:17px;font-weight:700}
.sidebar-menu{list-style:none;padding:12px 0;margin:0}
.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:13px 20px;color:#e5efff;text-decoration:none;font-size:14px}
.sidebar-menu a:hover,.sidebar-menu li.active a{background:#0d3765;color:#fff}
.sidebar-menu li.active a{border-left:4px solid #8fc1ff;padding-left:16px}
#content{flex:1;min-width:0}
.topbar{height:64px;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 24px}
.toggle-btn{border:0;background:transparent;color:#fff;font-size:24px}
.main-container{padding:28px}
.breadcrumb-text{font-size:14px;color:#718096;margin-bottom:22px}
.breadcrumb-text a{color:var(--blue);text-decoration:none}
.card-custom{background:#fff;border:0;border-radius:12px;padding:24px;box-shadow:0 3px 15px rgba(20,50,90,.06);height:100%}
.card-title-custom{font-size:19px;font-weight:700;color:var(--navy);margin-bottom:18px}
.stat-card{display:flex;align-items:center;gap:16px}
.stat-icon{width:52px;height:52px;border-radius:12px;display:grid;place-items:center;background:#eaf3ff;color:var(--blue);font-size:24px}
.stat-number{font-size:25px;font-weight:700;color:var(--navy)}
.stat-label{font-size:13px;color:#718096}
.table-custom th{font-size:13px;color:#526174;background:#f6f9fd}
.table-custom td{font-size:14px;vertical-align:middle}
@media(max-width:768px){#sidebar{width:220px;min-width:220px}#sidebar.collapsed{margin-left:-220px}.main-container{padding:16px}.topbar{padding:0 15px}}
</style>
</head>
<body>
<div class="wrapper">
<nav id="sidebar">
    <div class="sidebar-header"><i class="bi bi-mortarboard-fill me-2"></i>SDN 1 BERIRIJARAK</div>
    <ul class="sidebar-menu">
        <li class="<?= $page === "beranda" ? "active" : "" ?>"><a href="dashboard_admin.php?page=beranda"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
        <li class="<?= $page === "profil" ? "active" : "" ?>"><a href="dashboard_admin.php?page=profil"><i class="bi bi-building"></i>Profil Sekolah</a></li>
        <li class="<?= $page === "guru" ? "active" : "" ?>"><a href="dashboard_admin.php?page=guru"><i class="bi bi-person-workspace"></i>Data Guru</a></li>
        <li class="<?= $page === "siswa" ? "active" : "" ?>"><a href="dashboard_admin.php?page=siswa"><i class="bi bi-people-fill"></i>Data Siswa</a></li>
        <li class="<?= $page === "berita" ? "active" : "" ?>"><a href="dashboard_admin.php?page=berita"><i class="bi bi-newspaper"></i>Berita Sekolah</a></li>
        <li class="<?= $page === "galeri" ? "active" : "" ?>"><a href="dashboard_admin.php?page=galeri"><i class="bi bi-images"></i>Galeri Foto</a></li>
        <li class="<?= $page === "media" ? "active" : "" ?>"><a href="dashboard_admin.php?page=media"><i class="bi bi-journal-bookmark-fill"></i>Media Pembelajaran</a></li>
        <li class="mt-3 pt-2 border-top border-light border-opacity-25"><a href="logout.php"><i class="bi bi-box-arrow-left"></i>Logout</a></li>
    </ul>
</nav>
<div id="content">
    <header class="topbar">
        <button class="toggle-btn" id="sidebarCollapse" type="button" aria-label="Buka/tutup menu"><i class="bi bi-list"></i></button>
        <div><i class="bi bi-person-circle me-2"></i>Halo, <strong><?= htmlspecialchars($namaAdmin, ENT_QUOTES, "UTF-8") ?></strong></div>
    </header>
    <main class="main-container">
        <?php if (!$koneksi): ?><div class="alert alert-warning"><strong>Database belum terhubung.</strong> Periksa MySQL dan pastikan database <code>db_sdn1beririjarak_baru</code> sudah dibuat dan diisi melalui database.sql.</div><?php endif; ?>
        <div class="breadcrumb-text"><a href="dashboard_admin.php?page=beranda">Dashboard</a> / <?= htmlspecialchars(ucfirst($page), ENT_QUOTES, "UTF-8") ?></div>
        <?php if ($page === "beranda"): ?>
            <div class="mb-4"><h2 class="h4 fw-bold text-primary mb-1">Dashboard Administrator</h2><p class="text-muted mb-0">Selamat datang di panel pengelolaan website SDN 1 Beririjarak.</p></div>
            <div class="row g-3">
                <?php foreach ([["bi-people","Data Guru","tb_guru"],["bi-mortarboard","Data Siswa","tb_siswa"],["bi-newspaper","Berita Sekolah","tb_berita"],["bi-images","Galeri Foto","tb_galeri"]] as $item): $total=jumlahData($koneksi,$item[2]); ?>
                <div class="col-sm-6 col-xl-3"><div class="card-custom stat-card"><div class="stat-icon"><i class="bi <?= $item[0] ?>"></i></div><div><div class="stat-number"><?= $total === null ? "—" : $total ?></div><div class="stat-label"><?= $item[1] ?></div></div></div></div>
                <?php endforeach; ?>
            </div>
            <div class="card-custom mt-4"><h5 class="card-title-custom">Informasi Sistem</h5><p class="text-muted mb-0">Gunakan menu di sebelah kiri untuk mengelola profil sekolah, data guru dan siswa, berita, galeri, serta media pembelajaran. Angka ringkasan akan muncul setelah tabel terkait tersedia di database.</p></div>
        <?php elseif ($page === "profil"): ?>
            <div class="card-custom"><h5 class="card-title-custom">Pengelolaan Profil Sekolah</h5><p class="text-muted mb-0">Bagian ini disiapkan untuk pengelolaan informasi umum, sambutan kepala sekolah, visi dan misi, serta fasilitas SDN 1 Beririjarak.</p></div>
        <?php else: ?>
            <?php
            $titles = ["guru"=>"Data Guru","siswa"=>"Data Siswa","berita"=>"Berita Sekolah","galeri"=>"Galeri Foto","media"=>"Media Pembelajaran"];
            $descriptions = ["guru"=>"Kelola informasi guru dan tenaga kependidikan.","siswa"=>"Kelola data peserta didik SDN 1 Beririjarak.","berita"=>"Kelola berita, kegiatan, dan pengumuman sekolah.","galeri"=>"Kelola dokumentasi foto kegiatan sekolah.","media"=>"Kelola materi, video pembelajaran, dan latihan soal."];
            ?>
            <div class="card-custom"><h5 class="card-title-custom"><?= $titles[$page] ?></h5><p class="text-muted mb-0"><?= $descriptions[$page] ?></p><div class="alert alert-info mt-4 mb-0"><i class="bi bi-info-circle me-2"></i>Halaman ini sudah terhubung dengan navigasi dashboard. Fitur tambah, ubah, hapus, dan penyimpanan database akan diaktifkan pada tahap pengembangan berikutnya.</div></div>
        <?php endif; ?>
    </main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("sidebarCollapse").addEventListener("click",function(){document.getElementById("sidebar").classList.toggle("collapsed");});
</script>
</body>
</html>