<?php

require 'config/database.php';

$cari = isset($_GET['cari']) ? trim(preg_replace('/\s+/', ' ', $_GET['cari'])) : '';
$sort = isset($_GET['sort']) && $_GET['sort'] === 'desc' ? 'DESC' : 'ASC';

if ($cari !== '') {
    $safe = str_replace(['%', '_'], ['\%', '\_'], $cari);
    $like = "%$safe%";
    $stmt = $conn->prepare("
        SELECT * FROM jkp
        WHERE nama_pekerja LIKE ? OR nama_perusahaan LIKE ?
        ORDER BY tanggal_phk $sort
    ");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $query = $stmt->get_result();
} else {
    $sql = "SELECT * FROM jkp ORDER BY tanggal_phk $sort";
    $query = mysqli_query($conn, $sql);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>JKP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:linear-gradient(135deg,#f0f4ff,#e8edf5,#fafbfe);min-height:100vh;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
    .hero{background:linear-gradient(135deg,#0a2540,#1a3a6b,#2563eb);color:#fff;border-radius:24px;padding:28px 32px;box-shadow:0 20px 50px rgba(10,37,64,.15);margin-bottom:24px;text-align:center}
    .hero h1{font-weight:800;letter-spacing:4px;font-size:36px;margin:0}
    .hero p{color:rgba(255,255,255,.5);font-size:13px;margin-top:2px;letter-spacing:2px}
    .card-table{border:none;border-radius:20px;box-shadow:0 12px 40px rgba(16,24,40,.06);overflow:hidden}
    .card-table .card-header{background:#fff;border-bottom:1px solid #eef2f6;padding:20px 24px}
    .search-box{border-radius:14px;border:1px solid #e2e8f0;padding:12px 18px;font-size:14px;transition:.2s;background:#fff;width:100%}
    .search-box:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);outline:none}
    .btn-cari{background:#2563eb;color:#fff;border:none;border-radius:14px;padding:12px 24px;font-weight:600;transition:.2s;width:100%;font-size:14px}
    .btn-cari:hover{background:#1d4ed8;transform:translateY(-1px)}
    .table thead th{background:#f8fafc;color:#475569;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2e8f0;padding:14px 16px}
    .table td{padding:14px 16px;vertical-align:middle;border-top:1px solid #f1f5f9;color:#334155;font-size:14px}
    .table tbody tr:hover{background:#f8fafc}
    .badge-status{background:#eef2ff;color:#4338ca;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600}
    .footer-text{text-align:center;margin-top:24px;color:#94a3b8;font-size:13px;padding-bottom:20px}
    @media(max-width:576px){
        .hero{padding:20px 16px;border-radius:16px}
        .hero h1{font-size:24px}
        .table td,.table th{padding:10px 8px;font-size:13px}
    }
</style>
</head>
<body>

<div class="container py-4" style="max-width:960px">
    <div class="hero">
        <h1>JKP</h1>
        <p>DATA PENCATATAN PHK</p>
    </div>

    <div class="card card-table">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h5 class="mb-0 fw-bold" style="color:#0f172a"><i class="bi bi-list-ul me-2"></i>Data Pencatatan JKP</h5>
                <div class="d-flex gap-2">
                    <a href="?sort=asc" class="btn btn-sm <?= $sort === 'ASC' ? 'btn-primary' : 'btn-outline-secondary' ?>" style="border-radius:10px"><i class="bi bi-sort-alpha-down"></i></a>
                    <a href="?sort=desc" class="btn btn-sm <?= $sort === 'DESC' ? 'btn-primary' : 'btn-outline-secondary' ?>" style="border-radius:10px"><i class="bi bi-sort-alpha-down-alt"></i></a>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="GET" class="mb-4">
                <div class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="cari" value="<?= htmlspecialchars($cari); ?>" placeholder="Cari nama pekerja atau perusahaan..." class="form-control search-box">
                    </div>
                    <div class="col-md-2">
                        <button class="btn-cari"><i class="bi bi-search me-1"></i> Cari</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Pekerja</th>
                        <th>Perusahaan</th>
                        <th>Alasan PHK</th>
                        <th>
                            <a href="?sort=<?= $sort === 'ASC' ? 'desc' : 'asc' ?>" style="color:#475569;text-decoration:none;">
                                Tgl PHK <?= $sort === 'ASC' ? '↑' : '↓' ?>
                            </a>
                        </th>
                        <th>No Surat</th>
                        <th>Tgl Surat</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while($data=mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><span class="badge-status"><?= $no++; ?></span></td>
                        <td><b><?= htmlspecialchars($data['nama_pekerja']); ?></b></td>
                        <td><?= htmlspecialchars($data['nama_perusahaan']); ?></td>
                        <td><?= htmlspecialchars($data['alasan_phk']); ?></td>
                        <td><?= !empty($data['tanggal_phk']) ? date('d-m-Y', strtotime($data['tanggal_phk'])) : '-'; ?></td>
                        <td><?= htmlspecialchars($data['nomor_surat_lphk']); ?></td>
                        <td><?= !empty($data['tanggal_surat_lphk']) ? date('d-m-Y', strtotime($data['tanggal_surat_lphk'])) : '-'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($query) == 0): ?>
                    <tr><td colspan="7" class="text-center py-4" style="color:#94a3b8">Tidak ada data ditemukan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="footer-text">
        &copy; <?= date('Y'); ?> JKP
    </div>
</div>

</body>
</html>
