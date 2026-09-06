<?php
require '../config/database.php';
require '../auth/cek_login.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$fields = [
    'nomor_register'     => 'Nomor Register',
    'nama_perusahaan'    => 'Nama Perusahaan',
    'nama_pekerja'       => 'Nama Pekerja',
    'nik'                => 'NIK',
    'alasan_phk'         => 'Alasan PHK',
    'tanggal_phk'        => 'Tanggal PHK',
    'nomor_surat_lphk'   => 'Nomor Surat LPHK',
    'tanggal_surat_lphk' => 'Tanggal Surat LPHK',
];

$templateDir = realpath(__DIR__ . '/../templates');
$templatePath = $templateDir . DIRECTORY_SEPARATOR . 'template_jkp.xlsx';

mysqli_query($conn, "INSERT IGNORE INTO mapping_setting (id) VALUES (1)");
$s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM mapping_setting WHERE id=1 LIMIT 1"));

$isLocked = ($s && !empty($s['is_locked']));
$message = '';
$error   = '';

$halaman_aktif = 'mapping';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
}

function isValidCell($cell) {
    return preg_match('/^[A-Z]{1,3}\d+$/i', strtoupper(trim($cell)));
}

function cleanCell($cell) {
    return strtoupper(trim($cell));
}

function masterCellFor($cell, $mergeCells) {
    foreach ($mergeCells as $range) {
        list($start, $end) = explode(':', $range);
        list($sCol, $sRow) = Coordinate::coordinateFromString($start);
        list($eCol, $eRow) = Coordinate::coordinateFromString($end);
        list($cCol, $cRow) = Coordinate::coordinateFromString($cell);
        if ($cRow >= $sRow && $cRow <= $eRow && Coordinate::columnIndexFromString($cCol) >= Coordinate::columnIndexFromString($sCol) && Coordinate::columnIndexFromString($cCol) <= Coordinate::columnIndexFromString($eCol)) {
            return $start;
        }
    }
    return $cell;
}

if (isset($_FILES['template_file']) && $_FILES['template_file']['error'] === UPLOAD_ERR_OK) {
    if ($isLocked) {
        $error = 'Mapping terkunci. Buka kunci atau hapus setting dulu.';
    } else {
        $ext = strtolower(pathinfo($_FILES['template_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            $error = 'Hanya file .xlsx yang diperbolehkan.';
        } else {
            if (move_uploaded_file($_FILES['template_file']['tmp_name'], $templatePath)) {
                $message = 'Template berhasil diupload.';
            } else {
                $error = 'Gagal menyimpan file template.';
            }
        }
    }
}

if (isset($_POST['save_mapping'])) {
    if ($isLocked) {
        $error = 'Mapping sudah dikunci. Buka kunci atau hapus setting untuk mengubah posisi.';
    } else {
        $fieldName = trim($_POST['field_name'] ?? '');
        $excelCell = cleanCell($_POST['excel_cell'] ?? '');
        if (!array_key_exists($fieldName, $fields)) {
            $error = 'Field yang dipilih tidak valid.';
        } elseif (!isValidCell($excelCell)) {
            $error = 'Sel Excel tidak valid. Contoh: B7, D12, H25.';
        } elseif (!file_exists($templatePath)) {
            $error = 'Template belum diupload.';
        } else {
            try {
                $spreadsheet = IOFactory::load($templatePath);
                $sheet = $spreadsheet->getActiveSheet();
                $excelCell = masterCellFor($excelCell, $sheet->getMergeCells());
                $cek = $conn->prepare("SELECT id FROM mapping_excel WHERE field_name = ? LIMIT 1");
                $cek->bind_param('s', $fieldName);
                $cek->execute();
                $res = $cek->get_result();
                if ($res && $res->num_rows > 0) {
                    $upd = $conn->prepare("UPDATE mapping_excel SET excel_cell = ? WHERE field_name = ?");
                    $upd->bind_param('ss', $excelCell, $fieldName);
                    $upd->execute();
                } else {
                    $ins = $conn->prepare("INSERT INTO mapping_excel (field_name, excel_cell) VALUES (?, ?)");
                    $ins->bind_param('ss', $fieldName, $excelCell);
                    $ins->execute();
                }
                $message = 'Mapping disimpan. "' . $fields[$fieldName] . '" dipasang ke sel ' . $excelCell . '.';
            } catch (Throwable $e) {
                $error = 'Gagal menyimpan mapping. Periksa kembali sel dan isi template.';
            }
        }
    }
}

if (isset($_POST['lock_setting'])) {
    if (file_exists($templatePath)) {
        mysqli_query($conn, "UPDATE mapping_setting SET is_locked = 1, locked_at = NOW() WHERE id = 1");
        $isLocked = true;
        $message = 'Mapping sudah dikunci.';
    } else {
        $error = 'Template belum ada. Upload template dulu.';
    }
}

if (isset($_POST['unlock_setting'])) {
    mysqli_query($conn, "UPDATE mapping_setting SET is_locked = 0, locked_at = NULL WHERE id = 1");
    $isLocked = false;
    $message = 'Mapping dibuka kembali.';
}

if (isset($_POST['delete_single_mapping'])) {
    $deleteField = trim($_POST['delete_field'] ?? '');
    if ($deleteField !== '') {
        $stmt = $conn->prepare("DELETE FROM mapping_excel WHERE field_name = ?");
        $stmt->bind_param('s', $deleteField);
        $stmt->execute();
        $message = 'Mapping "' . htmlspecialchars($fields[$deleteField] ?? $deleteField) . '" berhasil dihapus.';
    }
}

if (isset($_POST['delete_setting'])) {
    mysqli_query($conn, "DELETE FROM mapping_excel");
    mysqli_query($conn, "UPDATE mapping_setting SET is_locked = 0, locked_at = NULL WHERE id = 1");
    $isLocked = false;
    $message = 'Semua mapping sudah dihapus.';
}

$mapping = [];
$mappingIds = [];
$qMap = mysqli_query($conn, "SELECT id, field_name, excel_cell FROM mapping_excel");
if ($qMap) {
    while ($r = mysqli_fetch_assoc($qMap)) {
        $mapping[$r['field_name']] = strtoupper(trim($r['excel_cell']));
        $mappingIds[$r['field_name']] = $r['id'];
    }
}

$previewReady = file_exists($templatePath);

$sheet = null;
$highestRow = 0;
$highestColIndex = 0;
$mergeStarts = [];
$coveredCells = [];

if ($previewReady) {
    try {
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestRow();
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        foreach ($sheet->getMergeCells() as $range) {
            [$start, $end] = explode(':', $range);
            [$startColLetters, $startRow] = Coordinate::coordinateFromString($start);
            [$endColLetters, $endRow] = Coordinate::coordinateFromString($end);
            $startCol = Coordinate::columnIndexFromString($startColLetters);
            $endCol   = Coordinate::columnIndexFromString($endColLetters);
            $mergeStarts[$start] = ['colspan' => $endCol - $startCol + 1, 'rowspan' => $endRow - $startRow + 1];
            for ($r = $startRow; $r <= $endRow; $r++) {
                for ($c = $startCol; $c <= $endCol; $c++) {
                    if ($r === $startRow && $c === $startCol) continue;
                    $coveredCells[$c . ':' . $r] = true;
                }
            }
        }
    } catch (Throwable $e) {
        $error = 'Template gagal dibaca. Gunakan file Excel (.xlsx) yang valid.';
        $previewReady = false;
    }
}

$cellToField = [];
foreach ($mapping as $field => $cell) {
    $cellToField[$cell] = $field;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapping Excel JKP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        body{background:linear-gradient(135deg,#061220,#081426,#102d63);min-height:100vh;color:#e0e7f0;font-family:system-ui,-apple-system,'Segoe UI',sans-serif}
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
        .glass-card .card-header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.07);border-radius:20px 20px 0 0!important;padding:16px 20px}
        .glass-card .card-body{padding:20px}

        .step-card{background:rgba(255,255,255,.06);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:18px;height:100%;box-shadow:0 12px 30px rgba(0,0,0,.2)}
        .step-number{width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;background:#3b82f6;color:#fff;margin-right:10px;flex-shrink:0;font-size:14px}

        .form-control,.form-select{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#fff;height:46px;border-radius:12px;padding:10px 14px}
        .form-control:focus,.form-select:focus{background:rgba(255,255,255,.12);border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.2);color:#fff}
        .form-control::placeholder{color:rgba(255,255,255,.3)}
        .form-select option{background:#0f172a;color:#fff}

        .btn-glass{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);color:#e0e7f0;border-radius:10px;padding:8px 16px;font-weight:600;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px}
        .btn-glass:hover{background:rgba(255,255,255,.13);color:#fff}
        .btn-glass.primary{background:rgba(59,130,246,.2);border-color:rgba(59,130,246,.25);color:#60a5fa}
        .btn-glass.primary:hover{background:rgba(59,130,246,.3)}
        .btn-glass.success{background:rgba(16,185,129,.2);border-color:rgba(16,185,129,.25);color:#34d399}
        .btn-glass.success:hover{background:rgba(16,185,129,.3)}
        .btn-glass.warning{background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.25);color:#fbbf24}
        .btn-glass.warning:hover{background:rgba(245,158,11,.3)}
        .btn-glass.danger{background:rgba(239,68,68,.2);border-color:rgba(239,68,68,.25);color:#f87171}
        .btn-glass.danger:hover{background:rgba(239,68,68,.3)}

        .hero-box{background:linear-gradient(135deg,rgba(59,130,246,.2),rgba(99,102,241,.15));border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:22px 26px;backdrop-filter:blur(12px)}

        .sheet-wrap{overflow:auto;max-height:70vh;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);border-radius:14px;padding:2px}
        .sheet-table{border-collapse:collapse;width:max-content;min-width:100%}
        .sheet-table th,.sheet-table td{border:1px solid rgba(255,255,255,.1);min-width:110px;height:46px;padding:5px 7px;vertical-align:middle;white-space:nowrap;color:#e0e7f0;font-size:13px}
        .sheet-table th{background:rgba(255,255,255,.06);position:sticky;top:0;z-index:3;text-align:center;font-weight:600;color:rgba(255,255,255,.7)}
        .row-head{background:rgba(255,255,255,.06);position:sticky;left:0;z-index:2;text-align:center;min-width:50px!important;width:50px;font-weight:600;color:rgba(255,255,255,.6)!important}
        .corner-head{position:sticky;left:0;top:0;z-index:4;background:rgba(255,255,255,.08)!important;min-width:50px!important;width:50px}
        .cell{cursor:pointer;position:relative;transition:.12s ease}
        .cell:hover{background:rgba(59,130,246,.1)}
        .cell.mapped{background:rgba(16,185,129,.15)!important}
        .cell.selected{outline:2px solid #3b82f6;outline-offset:-2px;background:rgba(59,130,246,.2)!important}
        .cell-value{display:block;font-size:12px;line-height:1.2;max-width:200px;overflow:hidden;text-overflow:ellipsis}
        .cell-code{display:block;font-size:10px;opacity:.5;margin-top:2px}

        .table{--bs-table-bg:transparent;--bs-table-color:#e0e7f0;color:var(--bs-table-color);margin-bottom:0}
        .table thead th{background:rgba(0,0,0,.25);--bs-table-color:#f1f5f9;color:var(--bs-table-color);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid rgba(255,255,255,.07)}
        .table td{background:rgba(255,255,255,.02);border-top:1px solid rgba(255,255,255,.05)}
        .table tbody tr:hover td{background:rgba(255,255,255,.06)}
        .table-bordered>:not(caption)>*{border-color:rgba(255,255,255,.08)!important}

        .small-muted{color:rgba(255,255,255,.45);font-size:13px}
        .help-text{font-size:13px;color:rgba(255,255,255,.4)}

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
            .step-card .card-body{padding:16px}
            .hero-box{padding:16px}
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
    <a href="data.php" class="nav-item"><i class="bi bi-table"></i> Data JKP</a>
    <a href="mapping.php" class="nav-item active"><i class="bi bi-grid-3x3-gap"></i> Mapping Excel</a>
    <div class="divider"></div>
    <a href="export_rekap.php" class="nav-item"><i class="bi bi-file-spreadsheet"></i> Export Rekap</a>
    <a href="../index.php" target="_blank" class="nav-item"><i class="bi bi-globe2"></i> Halaman Publik</a>
    <div class="divider"></div>
    <a href="../auth/logout.php" class="nav-item" style="color:rgba(239,68,68,.7)!important"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="topbar">
    <button class="btn btn-glass" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Mapping Excel</span>
    <span></span>
</div>

<div class="content">

    <div class="hero-box mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1 fw-bold" style="color:#fff"><i class="bi bi-grid-3x3-gap me-2"></i>Mapping Excel</h4>
                <div class="small-muted">Atur posisi data ke dalam sel template Excel.</div>
            </div>
            <a href="index.php" class="btn-glass primary btn-sm"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($isLocked): ?>
        <div class="alert" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:12px 16px;color:#f87171;font-size:14px"><i class="bi bi-lock-fill me-2"></i><b>Mapping terkunci.</b> Buka kunci atau hapus setting untuk mengubah posisi.</div>
    <?php endif; ?>
    <?php if ($message): ?><div class="alert" style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.2);border-radius:12px;padding:12px 16px;color:#34d399;font-size:14px"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:12px 16px;color:#f87171;font-size:14px"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="step-card">
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="step-number">1</div>
                        <div><h5 class="mb-1" style="color:#fff">Upload Template</h5><div class="help-text">File Excel yang dipakai.</div></div>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="color:rgba(255,255,255,.7);font-size:13px">File template Excel</label>
                            <input type="file" name="template_file" accept=".xlsx" class="form-control" <?= $isLocked ? 'disabled' : 'required' ?>>
                        </div>
                        <button type="submit" name="upload_template" class="btn-glass primary w-100" <?= $isLocked ? 'disabled' : '' ?>><i class="bi bi-upload"></i> Upload</button>
                    </form>
                    <hr style="border-color:rgba(255,255,255,.06);margin:16px 0">
                    <div class="small-muted mb-2">File aktif: <code style="color:rgba(255,255,255,.5)">templates/template_jkp.xlsx</code></div>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" class="d-inline">
                            <?= csrf_field(); ?>
                            <button type="submit" name="lock_setting" class="btn-glass primary btn-sm" <?= $isLocked ? 'disabled' : '' ?>><i class="bi bi-lock"></i> Kunci</button>
                        </form>
                        <form method="POST" class="d-inline">
                            <?= csrf_field(); ?>
                            <button type="submit" name="unlock_setting" class="btn-glass warning btn-sm" <?= !$isLocked ? 'disabled' : '' ?>><i class="bi bi-unlock"></i> Buka</button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus semua mapping?')">
                            <?= csrf_field(); ?>
                            <button type="submit" name="delete_setting" class="btn-glass danger btn-sm"><i class="bi bi-trash"></i> Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="step-card">
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="step-number">2</div>
                        <div><h5 class="mb-1" style="color:#fff">Pilih Data &amp; Sel</h5><div class="help-text">Klik sel di preview, lalu pilih data.</div></div>
                    </div>
                    <form method="POST" id="mappingForm">
                        <?= csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="color:rgba(255,255,255,.7);font-size:13px">Pilih data</label>
                            <select name="field_name" class="form-select" <?= $isLocked ? 'disabled' : 'required' ?>>
                                <option value="">-- pilih data --</option>
                                <?php foreach ($fields as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>">
                                        <?= htmlspecialchars($label) ?>
                                        <?php if (isset($mapping[$key])): ?> (di <?= htmlspecialchars($mapping[$key]) ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="color:rgba(255,255,255,.7);font-size:13px">Sel Excel</label>
                            <input type="text" name="excel_cell" id="excel_cell" class="form-control" placeholder="Klik sel di preview" <?= $isLocked ? 'disabled' : 'required' ?>>
                        </div>
                        <button type="submit" name="save_mapping" class="btn-glass success w-100" <?= $isLocked ? 'disabled' : '' ?>><i class="bi bi-check-lg"></i> Simpan Mapping</button>
                        <button type="button" class="btn-glass w-100 mt-2" onclick="document.getElementById('excel_cell').value='';"><i class="bi bi-x-circle"></i> Kosongkan</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="step-card">
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="step-number">3</div>
                        <div><h5 class="mb-1" style="color:#fff">Mapping Tersimpan</h5><div class="help-text">Posisi data yang aktif.</div></div>
                    </div>
                    <?php if (!empty($mapping)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead><tr><th>Data</th><th>Sel</th><th width="40">Aksi</th></tr></thead>
                                <tbody>
                                <?php foreach ($fields as $key => $label): ?>
                                    <?php if (isset($mapping[$key])): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($label) ?></td>
                                        <td><?= htmlspecialchars($mapping[$key]) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus mapping <?= htmlspecialchars($label) ?>?')">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="delete_field" value="<?= htmlspecialchars($key) ?>">
                                                <button type="submit" name="delete_single_mapping" class="btn-glass danger py-1 px-2" style="font-size:11px" <?= $isLocked ? 'disabled' : '' ?>>?</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3 small-muted">Belum ada mapping tersimpan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><h5 class="mb-1" style="color:#fff">Preview Template</h5><div class="help-text">Klik kotak untuk memilih sel.</div></div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge" style="background:rgba(59,130,246,.2);color:#60a5fa">Sel dipilih</span>
                    <span class="badge" style="background:rgba(16,185,129,.2);color:#34d399">Sudah dipetakan</span>
                </div>
            </div>
        </div>
        <div class="card-body p-3">
            <?php if (!$previewReady || !$sheet): ?>
                <div class="text-center py-4 small-muted">Template belum tersedia. Upload file Excel terlebih dahulu.</div>
            <?php else: ?>
                <div class="sheet-wrap">
                    <table class="sheet-table" id="sheetTable">
                        <thead>
                        <tr>
                            <th class="corner-head">#</th>
                            <?php for ($col = 1; $col <= $highestColIndex; $col++): ?>
                                <th><?= htmlspecialchars(Coordinate::stringFromColumnIndex($col)) ?></th>
                            <?php endfor; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php for ($row = 1; $row <= $highestRow; $row++): ?>
                            <tr>
                                <th class="row-head"><?= $row ?></th>
                                <?php for ($col = 1; $col <= $highestColIndex; $col++): ?>
                                    <?php
                                    $cell = Coordinate::stringFromColumnIndex($col) . $row;
                                    $skipKey = $col . ':' . $row;
                                    if (isset($coveredCells[$skipKey])) continue;
                                    $value = (string) $sheet->getCell($cell)->getFormattedValue();
                                    $value = trim($value);
                                    $isMergedStart = isset($mergeStarts[$cell]);
                                    $colspan = $isMergedStart ? $mergeStarts[$cell]['colspan'] : 1;
                                    $rowspan = $isMergedStart ? $mergeStarts[$cell]['rowspan'] : 1;
                                    $isMapped = isset($cellToField[$cell]);
                                    $fieldLabel = $isMapped ? $fields[$cellToField[$cell]] : '';
                                    ?>
                                    <td class="cell <?= $isMapped ? 'mapped' : '' ?>" data-cell="<?= htmlspecialchars($cell) ?>"
                                        <?= $colspan > 1 ? 'colspan="'.(int)$colspan.'"' : '' ?>
                                        <?= $rowspan > 1 ? 'rowspan="'.(int)$rowspan.'"' : '' ?>>
                                        <span class="cell-value"><?= $value !== '' ? htmlspecialchars($value) : '&nbsp;' ?></span>
                                        <span class="cell-code"><?= htmlspecialchars($cell) ?>
                                            <?php if ($isMapped): ?><span class="badge" style="background:rgba(16,185,129,.3);color:#34d399;font-size:10px"><?= htmlspecialchars($fieldLabel) ?></span><?php endif; ?>
                                        </span>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.cell').forEach(function(td){
    td.addEventListener('click', function(){
        if (document.getElementById('excel_cell').disabled) return;
        document.querySelectorAll('.cell.selected').forEach(function(el){ el.classList.remove('selected'); });
        this.classList.add('selected');
        document.getElementById('excel_cell').value = this.dataset.cell;
    });
});

function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>
</body>
</html>
