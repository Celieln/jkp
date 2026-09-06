<?php

require 'auth/boot.php';
require 'config/database.php';

if(isset($_SESSION['login'])){
    header("Location: dashboard/");
    exit;
}

$error = '';

$max_attempts = 5;
$lockout_window = 900;
$now = time();

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['login_try_time'])) $_SESSION['login_try_time'] = 0;

if ($now - (int)$_SESSION['login_try_time'] > $lockout_window) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_try_time'] = 0;
}

if(isset($_POST['login'])){
    csrf_require();

    if ($_SESSION['login_attempts'] >= $max_attempts) {
        $error = 'Terlalu banyak percobaan login. Silakan tunggu beberapa menit.';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if($row && password_verify($password, $row['password'])){
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_try_time'] = 0;
            session_regenerate_id(true);
            $_SESSION['login'] = true;
            $_SESSION['id'] = $row['id'];
            $_SESSION['nama'] = $row['nama_lengkap'];
            header("Location: dashboard/");
            exit;
        }

        $_SESSION['login_attempts']++;
        $_SESSION['login_try_time'] = $now;
        $sisa = $max_attempts - (int)$_SESSION['login_attempts'];
        $error = ($sisa > 0)
            ? "Username atau Password salah ($sisa percobaan tersisa)"
            : 'Terlalu banyak percobaan login. Silakan tunggu beberapa menit.';
    }
}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Admin - JKP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:linear-gradient(135deg,#061220,#081426,#102d63);min-height:100vh;display:flex;align-items:center;color:#e0e7f0;font-family:system-ui,-apple-system,\'Segoe UI\',sans-serif}
.login-card{background:rgba(255,255,255,.06);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.08);border-radius:24px;padding:40px 36px;box-shadow:0 30px 60px rgba(0,0,0,.35)}
.login-card .brand{text-align:center;margin-bottom:28px}
.login-card .brand h3{color:#fff;font-weight:800;letter-spacing:1px;font-size:22px}
.login-card .brand p{color:rgba(255,255,255,.35);font-size:12px;margin-top:4px}
.form-control{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#fff;height:50px;border-radius:14px;padding:12px 16px;font-size:14px}
.form-control:focus{background:rgba(255,255,255,.12);border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.2);color:#fff}
.form-control::placeholder{color:rgba(255,255,255,.3)}
label{font-size:13px;font-weight:600;color:rgba(255,255,255,.6);margin-bottom:6px}
.btn-login{width:100%;height:50px;border-radius:14px;font-weight:700;background:linear-gradient(135deg,#2563eb,#1d4ed8);border:none;color:#fff;box-shadow:0 4px 18px rgba(37,99,235,.35);transition:.2s;font-size:15px;letter-spacing:1px}
.btn-login:hover{background:linear-gradient(135deg,#1d4ed8,#1e40af);transform:translateY(-2px);box-shadow:0 8px 25px rgba(37,99,235,.45)}
.btn-login:active{transform:translateY(0)}
.back-link{display:block;text-align:center;margin-top:16px;color:rgba(255,255,255,.3);font-size:13px;text-decoration:none;transition:.2s}
.back-link:hover{color:rgba(255,255,255,.5)}
.alert-custom{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.15);border-radius:12px;padding:10px 14px;color:#f87171;font-size:13px;margin-bottom:18px}
</style>
</head>
<body>
<div class="container">
<div class="row justify-content-center">
<div class="col-md-5 col-lg-4">
<div class="login-card">
<div class="brand">
<h3><i class="bi bi-shield-fill-check me-2"></i>JKP</h3>
<p>Sistem Pencatatan PHK</p>
</div>
<h4 class="fw-bold mb-3 text-center" style="color:#fff;font-size:17px">Login Admin</h4>
<?php if($error): ?>
<div class="alert-custom"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
<?php endif; ?>
<form method="POST">
<?= csrf_field(); ?>
<div class="mb-3">
<label>Username</label>
<input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
</div>
<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
</div>
<button name="login" class="btn-login">LOGIN</button>
</form>
<a href="index.php" class="back-link"><i class="bi bi-arrow-left me-1"></i> Kembali ke halaman publik</a>
</div>
</div>
</div>
</div>
</body>
</html>
