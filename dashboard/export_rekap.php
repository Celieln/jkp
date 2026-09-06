<?php

require '../config/database.php';
require '../auth/cek_login.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$q = mysqli_query($conn, "SELECT * FROM jkp ORDER BY nomor_register ASC");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Rekap JKP');

$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', 'REKAP DATA JKP');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(1)->setRowHeight(35);

$sheet->mergeCells('A2:H2');
$sheet->setCellValue('A2', 'Tanggal Export: ' . date('d-m-Y H:i'));
$sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$headers = [
    'A' => 'No',
    'B' => 'Nomor Register',
    'C' => 'Nama Perusahaan',
    'D' => 'Nama Pekerja',
    'E' => 'NIK',
    'F' => 'Alasan PHK',
    'G' => 'Tanggal PHK',
    'H' => 'No Surat LPHK',
    'I' => 'Tanggal Surat LPHK',
];

$headerRow = 4;
foreach ($headers as $col => $label) {
    $sheet->setCellValue($col . $headerRow, $label);
}
$sheet->getStyle('A4:I4')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('A4:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A4:I4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle('A4:I4')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF0D6EFD');
$sheet->getStyle('A4:I4')->getFont()->getColor()->setARGB('FFFFFFFF');
$sheet->getRowDimension($headerRow)->setRowHeight(22);

$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF999999'],
        ],
    ],
];

$no = 1;
$row = 5;
while ($d = mysqli_fetch_assoc($q)) {
    $sheet->setCellValue('A' . $row, $no);
    $sheet->setCellValue('B' . $row, $d['nomor_register']);
    $sheet->setCellValue('C' . $row, $d['nama_perusahaan']);
    $sheet->setCellValue('D' . $row, $d['nama_pekerja']);
    $sheet->setCellValue('E' . $row, $d['nik']);
    $sheet->setCellValue('F' . $row, $d['alasan_phk']);
    $sheet->setCellValue('G' . $row, !empty($d['tanggal_phk']) ? date('d-m-Y', strtotime($d['tanggal_phk'])) : '');
    $sheet->setCellValue('H' . $row, $d['nomor_surat_lphk']);
    $sheet->setCellValue('I' . $row, !empty($d['tanggal_surat_lphk']) ? date('d-m-Y', strtotime($d['tanggal_surat_lphk'])) : '');

    $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($borderStyle);

    if ($no % 2 == 0) {
        $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF2F7FF');
    }

    $row++;
    $no++;
}

$lastRow = $row - 1;
$sheet->getStyle('A4:I' . $lastRow)->applyFromArray($borderStyle);

$sheet->getStyle('A4:I4')->applyFromArray($borderStyle);

$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(22);
$sheet->getColumnDimension('C')->setWidth(28);
$sheet->getColumnDimension('D')->setWidth(28);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(35);
$sheet->getColumnDimension('G')->setWidth(16);
$sheet->getColumnDimension('H')->setWidth(22);
$sheet->getColumnDimension('I')->setWidth(20);

$sheet->getStyle('A5:I' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
$sheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('G5:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('I5:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Rekap_JKP_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
