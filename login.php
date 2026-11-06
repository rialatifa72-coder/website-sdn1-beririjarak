<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/koneksi.php';

if (isset($_GET['aksi']) && $_GET['aksi'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi!';
    } else {
        $stmt = mysqli_prepare($koneksi, 'SELECT username, password FROM tb_users WHERE username = ? LIMIT 1');
        if (!$stmt) {
            $error = 'Query login gagal. Pastikan tabel tb_users sudah dibuat.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            if ($data) {
                $stored = (string)$data['password'];
                $valid = password_verify($password, $stored) || hash_equals($stored, $password);
                if ($valid) {
                    session_regenerate_id(true);
                    $_SESSION['username'] = $data['username'];
                    header('Location: dashboard_admin.php');
                    exit;
                }
            }
            $error = 'Username atau password yang dimasukkan salah.';
        }
    }
}
if (empty($_SESSION['username'])):
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | SDN 1 Beririjarak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,sans-serif;background:linear-gradient(135deg,#eaf3ff,#d8f3f0);min-height:100vh;display:flex;flex-direction:column;margin:0}
.navbar-sekolah,footer{background:#082b59;color:#fff}.navbar-sekolah{padding:15px 0}.navbar-brand{color:#fff!important;font-weight:700;text-decoration:none}.navbar-brand small{display:block;font-size:11px;font-weight:400}
.btn-home{color:#fff;text-decoration:none;border:1px solid #fff;padding:9px 18px;border-radius:6px}.login-container{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 15px}
.login-card{width:100%;max-width:430px;background:#fff;border-radius:18px;padding:40px;box-shadow:0 10px 35px #082b5920}.login-logo{width:110px;height:110px;margin:0 auto 20px;display:flex;align-items:center;justify-content:center}.login-logo img{max-width:100%;max-height:100%;object-fit:contain}
.login-title{text-align:center;color:#082b59;font-weight:700}.login-description{text-align:center;color:#777;font-size:14px;margin-bottom:28px}.form-label{font-weight:600;color:#26364a}.form-control{padding:12px;border-radius:8px}.btn-login{width:100%;padding:12px;background:#0d6efd;border:0;border-radius:8px;color:#fff;font-weight:600}footer{text-align:center;padding:17px;font-size:13px}
</style></head><body>
<nav class="navbar navbar-sekolah"><div class="container"><a href="index.php" class="navbar-brand"><i class="bi bi-mortarboard-fill me-2"></i>SDN 1 BERIRIJARAK<small>Sekolah Dasar Negeri</small></a><a href="index.php" class="btn-home"><i class="bi bi-house-door me-1"></i>Beranda</a></div></nav>
<main class="login-container"><div class="login-card"><div class="login-logo"><img src="logo.jpeg" alt="Logo SDN 1 Beririjarak"></div><h3 class="login-title">Website Sekolah</h3><p class="login-description">Silakan login untuk masuk ke halaman administrator SDN 1 Beririjarak.</p>
<?php if ($error !== ''): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="POST" action="login.php"><div class="mb-3"><label class="form-label" for="username">Username</label><input id="username" type="text" name="username" class="form-control" placeholder="Masukkan username" autocomplete="username" required></div><div class="mb-4"><label class="form-label" for="password">Password</label><input id="password" type="password" name="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required></div><button type="submit" class="btn-login"><i class="bi bi-box-arrow-in-right me-2"></i>Masuk</button></form>
</div></main><footer>&copy; 2026 SDN 1 Beririjarak. Semua hak dilindungi.</footer></body></html>
<?php exit; endif; header('Location: dashboard_admin.php'); exit; ?>
