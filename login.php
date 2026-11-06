<?php
session_start();

// Jika administrator sudah login, langsung buka dashboard.
if (isset($_SESSION['username'])) {
    header("Location: dashboard_admin.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi.";
    } else {
        require_once __DIR__ . "/koneksi.php";
        if (!$koneksi) {
            $error = "Database belum dapat dihubungkan. Pastikan MySQL aktif dan database db_sdn1beririjarak sudah dibuat melalui phpMyAdmin.";
        } else {
        $stmt = mysqli_prepare($koneksi, "SELECT username, password FROM tb_users WHERE username = ? LIMIT 1");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            $executed = mysqli_stmt_execute($stmt);

            if (!$executed) {
                $error = "Pemeriksaan login gagal. Periksa tabel tb_users pada database.";
                mysqli_stmt_close($stmt);
                $admin = null;
            } else {
                mysqli_stmt_bind_result($stmt, $dbUsername, $dbPassword);
                $found = mysqli_stmt_fetch($stmt);
                $admin = $found ? ["username" => $dbUsername, "password" => $dbPassword] : null;
                mysqli_stmt_close($stmt);
            }

            $valid = false;
            if ($admin) {
                $storedPassword = (string) $admin["password"];
                $valid = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

                // Migrasi otomatis password lama berbentuk teks biasa menjadi hash.
                if ($valid && !password_get_info($storedPassword)["algo"]) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = mysqli_prepare($koneksi, "UPDATE tb_users SET password = ? WHERE username = ?");
                    if ($update) {
                        mysqli_stmt_bind_param($update, "ss", $newHash, $username);
                        mysqli_stmt_execute($update);
                        mysqli_stmt_close($update);
                    }
                }
            }

            if ($valid) {
                session_regenerate_id(true);
                $_SESSION["username"] = $admin["username"];
                header("Location: dashboard_admin.php");
                exit;
            }

            if ($error === "") {
                $error = "Username atau password tidak sesuai.";
            }
        } else {
            $error = "Tidak dapat memproses login. Pastikan tabel tb_users sudah dibuat.";
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator | SDN 1 Beririjarak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;flex-direction:column;font-family:'Segoe UI',Tahoma,sans-serif;background:linear-gradient(135deg,#eaf3ff,#d8f3f0)}
        .navbar-sekolah,footer{background:#082b59;color:#fff}
        .navbar-sekolah{padding:15px 0}
        .brand{color:#fff;text-decoration:none;font-size:18px;font-weight:700}
        .brand small{display:block;font-size:11px;font-weight:400}
        .btn-home{color:#fff;text-decoration:none;border:1px solid #fff;border-radius:7px;padding:8px 14px}
        .btn-home:hover{background:#fff;color:#082b59}
        .login-container{flex:1;display:flex;align-items:center;justify-content:center;padding:35px 15px}
        .login-card{width:100%;max-width:430px;padding:38px;background:#fff;border-radius:18px;box-shadow:0 10px 35px rgba(8,43,89,.12)}
        .login-logo{width:105px;height:105px;margin:0 auto 18px;display:flex;align-items:center;justify-content:center}
        .login-logo img{max-width:100%;max-height:100%;object-fit:contain}
        .login-title{text-align:center;color:#082b59;font-weight:700}
        .login-description{text-align:center;color:#6b7280;font-size:14px;margin-bottom:26px}
        .form-label{font-weight:600;color:#26364a;font-size:14px}
        .form-control{padding:12px;border-radius:8px}
        .password-wrap{position:relative}
        .password-wrap .form-control{padding-right:45px}
        .btn-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:0;background:none;color:#082b59}
        .btn-login{width:100%;padding:12px;border:0;border-radius:8px;background:#0d6efd;color:#fff;font-weight:600}
        .btn-login:hover{background:#082b59}
        footer{text-align:center;padding:16px;font-size:13px}
        @media(max-width:576px){.login-card{padding:28px 22px}.brand{font-size:15px}}
    </style>
</head>
<body>
<nav class="navbar-sekolah">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="brand"><i class="bi bi-mortarboard-fill me-2"></i>SDN 1 BERIRIJARAK<small>Sekolah Dasar Negeri</small></a>
        <a href="index.php" class="btn-home"><i class="bi bi-house-door me-1"></i> Beranda</a>
    </div>
</nav>
<main class="login-container">
    <div class="login-card">
        <div class="login-logo"><img src="logo.jpeg" alt="Logo SDN 1 Beririjarak"></div>
        <h3 class="login-title">Login Administrator</h3>
        <p class="login-description">Masuk untuk mengelola informasi website SDN 1 Beririjarak.</p>
        <?php if ($error !== ""): ?>
            <div class="alert alert-danger py-2" role="alert"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php" autocomplete="on">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input id="username" type="text" name="username" class="form-control" placeholder="Masukkan username" autocomplete="username" required autofocus>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="password-wrap">
                    <input id="password" type="password" name="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
                    <button class="btn-eye" type="button" id="togglePassword" aria-label="Tampilkan password"><i class="bi bi-eye" id="passwordIcon"></i></button>
                </div>
            </div>
            <button type="submit" class="btn-login"><i class="bi bi-box-arrow-in-right me-2"></i>Masuk</button>
        </form>
    </div>
</main>
<footer>&copy; <?= date("Y") ?> SDN 1 Beririjarak. Semua hak dilindungi.</footer>
<script>
document.getElementById("togglePassword").addEventListener("click", function () {
    const field = document.getElementById("password");
    const icon = document.getElementById("passwordIcon");
    const show = field.type === "password";
    field.type = show ? "text" : "password";
    icon.className = show ? "bi bi-eye-slash" : "bi bi-eye";
});
</script>
</body>
</html>
