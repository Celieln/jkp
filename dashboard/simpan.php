<?php

require '../config/database.php';
require '../auth/cek_login.php';

csrf_require();



$nomor_register=$_POST['nomor_register'];

$nama_perusahaan=$_POST['nama_perusahaan'];

$nama_pekerja=$_POST['nama_pekerja'];

$nik=$_POST['nik'];

$alasan_phk=$_POST['alasan_phk'];

$tanggal_phk=$_POST['tanggal_phk'];

$nomor_surat_lphk=$_POST['nomor_surat_lphk'];

$tanggal_surat_lphk=$_POST['tanggal_surat_lphk'];



$stmt=$conn->prepare(

"INSERT INTO jkp(

nomor_register,

nama_perusahaan,

nama_pekerja,

nik,

alasan_phk,

tanggal_phk,

nomor_surat_lphk,

tanggal_surat_lphk

)

VALUES(

?,?,?,?,?,?,?,?

)"

);



$stmt->bind_param(

"ssssssss",

$nomor_register,

$nama_perusahaan,

$nama_pekerja,

$nik,

$alasan_phk,

$tanggal_phk,

$nomor_surat_lphk,

$tanggal_surat_lphk

);



$stmt->execute();


header("location:data.php");
exit;

?>