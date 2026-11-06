<?php
// Menyalakan error reporting sementara
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "koneksi.php";

// ==========================================
// 1. FITUR LOGOUT 
// ==========================================
// Menangkap aksi logout dari URL dan diarahkan kembali ke index.php
if (isset($_GET['aksi']) && $_GET['aksi'] == 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

$error = "";

// ==========================================
// 2. FITUR PROSES LOGIN
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi!";
    } else {
        $query = mysqli_prepare($koneksi, "SELECT username, password FROM tb_users WHERE username = ? LIMIT 1");
        
        if (!$query) {
            die("Query gagal: " . mysqli_error($koneksi));
        }

        mysqli_stmt_bind_param($query, "s", $username);
        mysqli_stmt_execute($query);

        $result = mysqli_stmt_get_result($query);
        $data = mysqli_fetch_assoc($result);

        $loginBerhasil = false;

        if ($data) {
            $passwordDatabase = $data['password'];
            if (password_verify($password, $passwordDatabase) || $password === $passwordDatabase) {
                $loginBerhasil = true;
            }
        }

        if ($loginBerhasil) {
            session_regenerate_id(true);
            $_SESSION['username'] = $data['username'];
            header("Location: dashboard_admin.php");
            exit;
        } else {
            $error = "Username atau password salah!";
        }
        
        mysqli_stmt_close($query);
    }
}

// ==========================================
// BAGIAN A: TAMPILAN JIKA "BELUM LOGIN" (FORM LOGIN)
// ==========================================
if (!isset($_SESSION['username'])) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SDN 1 Beririjarak</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: linear-gradient(135deg, #eaf3ff, #d8f3f0); min-height: 100vh; display: flex; flex-direction: column; }
        .navbar-sekolah { background: #082b59; padding: 15px 0; }
        .navbar-brand { color: white !important; font-size: 19px; font-weight: 700; }
        .navbar-brand small { display: block; font-size: 11px; font-weight: normal; }
        .btn-home { color: white; text-decoration: none; padding: 9px 18px; border: 1px solid white; border-radius: 6px; transition: .3s; }
        .btn-home:hover { background: white; color: #082b59; }
        .login-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 15px; }
        .login-card { width: 100%; max-width: 430px; background: white; border-radius: 18px; padding: 40px; box-shadow: 0 10px 35px rgba(8, 43, 89, .12); }
        .login-logo { width: 110px; height: 110px; background: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; overflow: hidden; }
        .login-logo img { width: 100%; height: 100%; object-fit: contain; padding: 5px; }
        .login-title { text-align: center; color: #082b59; font-size: 25px; font-weight: 700; margin-bottom: 8px; }
        .login-description { text-align: center; color: #777; font-size: 14px; line-height: 1.6; margin-bottom: 30px; }
        .form-label { font-weight: 600; color: #26364a; font-size: 14px; display: block; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #dce3ed; }
        .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13, 110, 253, .1); }
        .password-wrapper { position: relative; }
        .password-wrapper .form-control { padding-right: 45px; }
        .btn-eye { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #082b59; font-size: 18px; cursor: pointer; }
        .btn-login { width: 100%; padding: 12px; background: #0d6efd; border: none; border-radius: 8px; color: white; font-weight: 600; font-size: 15px; transition: .3s; }
        .btn-login:hover { background: #082b59; color: white; }
        .alert { font-size: 13px; border-radius: 8px; }
        .info-login { margin-top: 20px; padding: 15px; background: #eaf3ff; border-radius: 10px; }
        .judul-info { color: #082b59; font-weight: 600; font-size: 14px; margin-bottom: 12px; }
        .detail-login { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #26364a; }
        footer { background: #082b59; color: white; text-align: center; padding: 17px; font-size: 13px; }
        @media (max-width: 576px) { .login-card { padding: 28px 22px; } .login-title { font-size: 22px; } .navbar-brand { font-size: 16px; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-sekolah">
        <div class="container">
            <a href="index.php" class="navbar-brand text-decoration-none">
                <i class="bi bi-mortarboard-fill me-2"></i> SDN 1 BERIRIJARAK <small>Sekolah Dasar Negeri</small>
            </a>
            <a href="index.php" class="btn-home"><i class="bi bi-house-door me-1"></i> Beranda</a>
        </div>
    </nav>

    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="logo.jpeg" alt="Logo SDN 1 Beririjarak">
            </div>
            <h3 class="login-title">Website Sekolah</h3>
            <p class="login-description">Silakan login untuk masuk ke halaman administrator SDN 1 Beririjarak.</p>

            <?php if ($error != "") { ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php } ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= isset($username) ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="Masukkan username" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                        <button class="btn-eye" type="button" onclick="togglePassword()"><i class="bi bi-eye" id="iconPassword"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn-login"><i class="bi bi-box-arrow-in-right me-2"></i> Login</button>
            </form>

            <div class="info-login">
                <p class="judul-info"><i class="bi bi-info-circle-fill me-1"></i> Informasi Login Administrator</p>
                <div class="detail-login"><span>Username</span><strong>admin</strong></div>
                <div class="detail-login"><span>Password</span><strong>12345</strong></div>
            </div>
        </div>
    </div>

    <footer>&copy; 2026 SDN 1 Beririjarak. Semua hak dilindungi.</footer>

    <script>
        function togglePassword() {
            const password = document.getElementById("password");
            const icon = document.getElementById("iconPassword");
            if (password.type === "password") { 
                password.type = "text"; 
                icon.classList.replace("bi-eye", "bi-eye-slash"); 
            } else { 
                password.type = "password"; 
                icon.classList.replace("bi-eye-slash", "bi-eye"); 
            }
        }
    </script>
</body>
</html>
<?php
    exit; 
}


// ==========================================
// BAGIAN B: TAMPILAN JIKA "SUDAH LOGIN" (DASHBOARD ADMIN)
// ==========================================
$namaAdmin = $_SESSION['username'];
$page = isset($_GET['page']) ? $_GET['page'] : 'profil';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | SDN 1 Beririjarak</title>
    
    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- BOOTSTRAP ICONS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --topbar-bg: #2f5e97;
            --sidebar-header: #2c5f95;
            --sidebar-bg: #466cb8;
            --sidebar-active: #2d2247;
            --sidebar-hover: #372a54;
            --bg-light: #f4f5f8;
            --text-muted: #b0a8c0;
        }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-light); margin: 0; overflow-x: hidden; }
        .wrapper { display: flex; min-height: 100vh; }
        
        #sidebar { width: 240px; min-width: 240px; background-color: var(--sidebar-bg); color: var(--text-muted); transition: all 0.3s; }
        #sidebar.collapsed { margin-left: -240px; }
        
        .sidebar-header { background-color: var(--sidebar-header); padding: 16px 20px; font-size: 18px; font-weight: bold; color: #ffffff; display: flex; align-items: center; gap: 10px; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li a { display: flex; align-items: center; padding: 12px 20px; color: #d1cbe0; text-decoration: none; font-size: 14px; transition: 0.2s; }
        .sidebar-menu li a:hover { background-color: var(--sidebar-hover); color: #ffffff; }
        .sidebar-menu li.active > a { background-color: var(--sidebar-active); color: #ffffff; font-weight: 600; border-left: 4px solid #a470ff; }
        .sidebar-menu li a i { margin-right: 12px; font-size: 16px; }

        #content { flex-grow: 1; display: flex; flex-direction: column; }
        
        .topbar { background-color: var(--topbar-bg); height: 56px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; color: white; }
        .topbar .toggle-btn { background: transparent; border: none; color: white; font-size: 22px; cursor: pointer; }
        .topbar .user-profile { display: flex; align-items: center; gap: 15px; font-size: 14px; }
        
        .main-container { padding: 25px; }
        .breadcrumb-text { font-size: 14px; color: #6b7280; margin-bottom: 20px; }
        .breadcrumb-text a { color: #4c3575; text-decoration: none; font-weight: 500; }
        
        .card-custom { background: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04); padding: 25px; border: none; }
        .card-title-custom { font-size: 18px; color: #374151; margin-bottom: 20px; font-weight: 600; }
        
        .btn-success-custom { background-color: #10b981; border: none; color: white; font-size: 13px; padding: 7px 15px; border-radius: 4px; font-weight: 500; }
        .btn-success-custom:hover { background-color: #059669; color: white; }
        .btn-outline-custom { background-color: #f3f4f6; border: 1px solid #e5e7eb; color: #374151; font-size: 13px; padding: 7px 15px; border-radius: 4px; }
        
        .table-custom { margin-top: 15px; font-size: 14px; color: #4b5563; }
        .table-custom th { font-weight: 600; color: #374151; border-bottom: 2px solid #edf2f7; padding: 12px; }
        .table-custom td { vertical-align: middle; padding: 12px; border-bottom: 1px solid #edf2f7; }
        
        .form-switch .form-check-input { width: 38px; height: 20px; cursor: pointer; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- SIDEBAR KIRI -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-mortarboard-fill"></i> SDN 1 BERIRIJARAK
        </div>

        <ul class="sidebar-menu">
            <li class="<?= ($page == 'beranda') ? 'active' : '' ?>">
                <a href="dashboard_admin.php?page=beranda"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </li>
            <li class="<?= ($page == 'profil') ? 'active' : '' ?>">
                <a href="dashboard_admin.php?page=profil"><i class="bi bi-building"></i> Profil Sekolah</a>
            </li>
            <li class="<?= ($page == 'guru') ? 'active' : '' ?>">
                <a href="dashboard_admin.php?page=guru"><i class="bi bi-person-workspace"></i> Data Guru</a>
            </li>
            <li class="<?= ($page == 'siswa') ? 'active' : '' ?>">
                <a href="dashboard_admin.php?page=siswa"><i class="bi bi-people-fill"></i> Data Siswa</a>
            </li>
            <li class="<?= ($page == 'berita') ? 'active' : '' ?>">
                <a href="dashboard_admin.php?page=berita"><i class="bi bi-newspaper"></i> Berita Sekolah</a>
            </li>
            <li class="<?= ($page == 'galeri') ? 'active' : '' ?>">
                <a href="dashboard_admin.php?page=galeri"><i class="bi bi-images"></i> Galeri Foto</a>
            </li>
            <li class="<?= ($page == 'media') ? 'active' : '' ?>">
                <a href="dashboard_admin.php?page=media"><i class="bi bi-journal-bookmark-fill"></i> Media Belajar</a>
            </li>

            <!-- MENU LOGOUT DI BAWAH MEDIA PEMBELAJARAN -->
            <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 5px;">
                <a href="dashboard_admin.php?aksi=logout" style="color: #ffb4b4;">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- KONTEN UTAMA -->
    <div id="content">
        <!-- TOPBAR -->
        <div class="topbar">
            <button class="toggle-btn" id="sidebarCollapse">
                <i class="bi bi-list"></i>
            </button>

            <div class="user-profile">
                <span>Halo, <strong><?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8') ?></strong></span>
                <i class="bi bi-gear fs-5" style="cursor: pointer;"></i>
                
                <a href="dashboard_admin.php?aksi=logout" class="text-white text-decoration-none ms-2" title="Keluar">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                </a>
            </div>
        </div>

        <!-- ISI KONTEN BERDASARKAN NAVIGASI -->
        <div class="main-container">
            <div class="breadcrumb-text">
                <a href="dashboard_admin.php?page=beranda">Home</a> / <?= ucfirst($page) ?> Manager
            </div>

            <?php if ($page == 'profil'): ?>
                <div class="card card-custom">
                    <h5 class="card-title-custom">Pengelolaan Profil Sekolah</h5>
                    <p class="text-muted">Kelola informasi umum, Visi, Misi, serta fasilitas SDN 1 Beririjarak.</p>
                </div>
            <?php elseif ($page == 'guru'): ?>
                <div class="card card-custom">
                    <h5 class="card-title-custom">Daftar Data Guru</h5>
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-success-custom"><i class="bi bi-plus-lg"></i> Tambah Guru</button>
                        <button class="btn btn-outline-custom"><i class="bi bi-list-task me-1"></i> Export Data</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>Nama Guru</th>
                                    <th>NIP</th>
                                    <th>Jabatan / Mata Pelajaran</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 140px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td><td>-</td><td>-</td><td>-</td>
                                    <td><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" checked></div></td>
                                    <td><button class="btn btn-success btn-sm px-2 py-1 fs-7"><i class="bi bi-pencil-square"></i> Edit</button> <button class="btn btn-danger btn-sm px-2 py-1 fs-7"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($page == 'siswa'): ?>
                <div class="card card-custom">
                    <h5 class="card-title-custom">Daftar Data Siswa</h5>
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-success-custom"><i class="bi bi-plus-lg"></i> Tambah Siswa</button>
                        <button class="btn btn-outline-custom"><i class="bi bi-list-task me-1"></i> Export Data</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                    <th>Kelas</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 140px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td><td>-</td><td>-</td><td>-</td>
                                    <td><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" checked></div></td>
                                    <td><button class="btn btn-success btn-sm px-2 py-1 fs-7"><i class="bi bi-pencil-square"></i> Edit</button> <button class="btn btn-danger btn-sm px-2 py-1 fs-7"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($page == 'berita'): ?>
                <div class="card card-custom">
                    <h5 class="card-title-custom">Daftar Berita Sekolah</h5>
                    <p class="text-muted">Kelola postingan dan berita sekolah di sini.</p>
                </div>
            <?php else: ?>
                <div class="card card-custom">
                    <h5 class="card-title-custom">Dashboard Informasi</h5>
                    <p class="text-muted">Selamat datang di Panel Admin SDN 1 Beririjarak.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- BOOTSTRAP JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Fitur toggle buka/tutup sidebar
    document.getElementById('sidebarCollapse').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });
</script>

</body>
</html>