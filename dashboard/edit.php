<?php

require '../config/database.php';
require '../auth/cek_login.php';

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM jkp WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if(!$data){
    die("Data tidak ditemukan");
}

$halaman_aktif = 'data';

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Data JKP</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
    *{box-sizing:border-box}
    body{background:linear-gradient(135deg,#061220,#081426,#102d63);min-height:100vh;color:#e0e7f0;font-family:system-ui,-apple-system,\'Segoe UI\',sans-serif}
    ::-webkit-scrollbar{width:6px;height:6px}
    ::-webkit-scrollbar-track{background:rgba(255,255,255,.05);border-radius:10px}
    ::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:10px}

    .sidebar{width:260px;position:fixed;left:0;top:0;bottom:0;z-index:1040;background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border-right:1px solid rgba(255,255,255,.07);padding:0;overflow-y:auto;transition:transform .3s ease}
    .sidebar .brand{padding:22px 20px 16px;border-bottom:1px solid rgba(255,255,255,.07);text-align:center}
    .sidebar .brand h5{color:#fff;font-weight:700;letter-spacing:1px;margin:0;font-size:17px}
    .sidebar .brand small{color:rgba(255,255,255,.4);font-size:11px;display:block;margin-top:3px}
    .sidebar .nav-item{display:flex;align-items:center;gap:12px;padding:12px 20px;margin:3px 12px;border-radius:12px;color:rgba(255,255,255,.75);text-decoration:none;transition:.2s;font-weight:500;font-size:14px}
    .sidebar .nav-item:hover{background:rgba(255,255,255,.08);color:#fff;transform:translateX(3px)}
    .sidebar .nav-item.active{background:rgba(59,130,246,.25);color:#fff;box-shadow:inset 3px 0 0 #3b82f6}
    .sidebar .nav-item i{font-size:18px;width:22px;text-align:center}
    .sidebar .divider{height:1px;background:rgba(255,255,255,.06);margin:10px 20px}

    .content{margin-left:260px;padding:24px;min-height:100vh}
    .topbar{display:none;background:rgba(255,255,255,.05);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,.07);padding:12px 18px;position:sticky;top:0;z-index:1030;align-items:center;justify-content:space-between}
    .topbar .brand-mobile{color:#fff;font-weight:700;font-size:16px;letter-spacing:1px}

    .glass-card{background:rgba(255,255,255,.06);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:20px;box-shadow:0 20px 40px rgba(0,0,0,.25)}
    .glass-card .card-header{background:rgba(245,158,11,.15)!important;border-bottom:1px solid rgba(255,255,255,.07);border-radius:20px 20px 0 0!important;padding:16px 20px}
    .glass-card .card-body{padding:24px}

    .form-control,.form-select{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#fff;height:48px;border-radius:12px;padding:10px 14px}
    .form-control:focus,.form-select:focus{background:rgba(255,255,255,.12);border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.2);color:#fff}
    .form-control::placeholder{color:rgba(255,255,255,.3)}
    textarea.form-control{height:auto;min-height:90px;resize:vertical}

    label{font-weight:600;font-size:13px;margin-bottom:6px;color:rgba(255,255,255,.7);letter-spacing:.3px}

    .section-title{font-size:15px;font-weight:700;margin-bottom:18px;border-left:3px solid #f59e0b;padding-left:14px;color:#fff;letter-spacing:.5px;text-transform:uppercase}

    .btn-glass{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);color:#e0e7f0;border-radius:12px;padding:12px 24px;font-weight:700;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-size:14px;border:none;height:48px}
    .btn-glass:hover{background:rgba(255,255,255,.13);color:#fff;transform:translateY(-1px)}
    .btn-glass.primary{background:linear-gradient(135deg,#2563eb,#1d4ed8);box-shadow:0 4px 15px rgba(37,99,235,.3)}
    .btn-glass.primary:hover{background:linear-gradient(135deg,#1d4ed8,#1e40af);box-shadow:0 6px 20px rgba(37,99,235,.4)}
    .btn-glass.secondary{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1)}
    .btn-glass.secondary:hover{background:rgba(255,255,255,.13)}

    .small-muted{color:rgba(255,255,255,.45);font-size:13px}

    @media(max-width:992px){
        .sidebar{transform:translateX(-100%)}
        .sidebar.show{transform:translateX(0)}
        .content{margin-left:0;padding:16px}
        .topbar{display:flex}
        .overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1035;display:none}
        .overlay.show{display:block}
    }
    @media(max-width:576px){
        .content{padding:12px}
        .glass-card .card-body{padding:16px}
    }
</style>
</head>
<body>

<div class="overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="brand">
        <h5><i class="bi bi-shield-fill-check me-2"></i>JKP</h5>
        <small>Sistem Pencatatan PHK</small>
    </div>
    <a href="index.php" class="nav-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="tambah.php" class="nav-item"><i class="bi bi-plus-circle"></i> Tambah Data</a>
    <a href="data.php" class="nav-item active"><i class="bi bi-table"></i> Data JKP</a>
    <a href="mapping.php" class="nav-item"><i class="bi bi-grid-3x3-gap"></i> Mapping Excel</a>
    <div class="divider"></div>
    <a href="export_rekap.php" class="nav-item"><i class="bi bi-file-spreadsheet"></i> Export Rekap</a>
    <a href="../index.php" target="_blank" class="nav-item"><i class="bi bi-globe2"></i> Halaman Publik</a>
    <div class="divider"></div>
    <a href="../auth/logout.php" class="nav-item" style="color:rgba(239,68,68,.7)!important"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="topbar">
    <button class="btn btn-glass" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Edit Data</span>
    <span></span>
</div>

<div class="content">
<div class="glass-card">
<div class="card-header">
    <h4 class="fw-bold mb-0" style="color:#fff"><i class="bi bi-pencil-square me-2"></i>Edit Data JKP</h4>
</div>
<div class="card-body">
<form action="update.php" method="POST">
<input type="hidden" name="id" value="<?= $data['id']; ?>">
<?= csrf_field(); ?>

<div class="section-title">Data PHK</div>
<div class="row g-3 mb-4">
<div class="col-md-6">
<label>Nomor Register</label>
<input type="text" name="nomor_register" class="form-control" value="<?= htmlspecialchars($data['nomor_register']); ?>" required>
</div>
<div class="col-md-6">
<label>Nama Perusahaan</label>
<input type="text" name="nama_perusahaan" class="form-control" value="<?= htmlspecialchars($data['nama_perusahaan']); ?>" required>
</div>
<div class="col-md-6">
<label>Nama Pekerja</label>
<input type="text" name="nama_pekerja" class="form-control" value="<?= htmlspecialchars($data['nama_pekerja']); ?>" required>
</div>
<div class="col-md-3">
<label>NIK</label>
<input type="text" name="nik" class="form-control" maxlength="16" value="<?= htmlspecialchars($data['nik']); ?>" required>
</div>
<div class="col-md-3">
<label>Tanggal PHK</label>
<input type="date" name="tanggal_phk" class="form-control" value="<?= $data['tanggal_phk']; ?>" required>
</div>
<div class="col-12">
<label>Alasan PHK</label>
<textarea name="alasan_phk" class="form-control" required><?= htmlspecialchars($data['alasan_phk']); ?></textarea>
</div>
</div>

<div class="section-title">Data Surat LPHK</div>
<div class="row g-3 mb-4">
<div class="col-md-6">
<label>Nomor Surat LPHK</label>
<input type="text" name="nomor_surat_lphk" class="form-control" value="<?= htmlspecialchars($data['nomor_surat_lphk']); ?>" required>
</div>
<div class="col-md-6">
<label>Tanggal Surat LPHK</label>
<input type="date" name="tanggal_surat_lphk" class="form-control" value="<?= $data['tanggal_surat_lphk']; ?>" required>
</div>
</div>

<div class="d-flex gap-2 pt-2">
<button class="btn-glass primary px-4"><i class="bi bi-check-lg"></i> Update</button>
<a href="data.php" class="btn-glass secondary px-4"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

</form>
</div>
</div>
</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>
</body>
</html>
