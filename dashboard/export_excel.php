<?php

require '../config/database.php';
require '../auth/cek_login.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;



$templatePath = "../templates/template_jkp.xlsx";


if(!file_exists($templatePath))
{

die("Template tidak ditemukan");

}



$id = isset($_GET['id'])

? (int)$_GET['id']

:0;



$q = mysqli_query(

$conn,

"SELECT *

FROM jkp

WHERE id='$id'

LIMIT 1"

);



if(mysqli_num_rows($q)==0)
{

die("Data tidak ditemukan");

}



$d = mysqli_fetch_assoc($q);



/*==========================
AMBIL MAPPING
===========================*/


$mapping=[];


$qMap = mysqli_query(

$conn,

"SELECT *

FROM mapping_excel"

);


while(

$r=mysqli_fetch_assoc($qMap)

)

{

$mapping[

$r['field_name']

]

=

strtoupper(

trim(

$r['excel_cell']

)

);

}



if(empty($mapping))
{

die(

"Mapping belum dibuat"

);

}




$spreadsheet = IOFactory::load(

$templatePath

);


$sheet = $spreadsheet->getActiveSheet();



$mergeCells = $sheet->getMergeCells();




function masterCellForTarget(

$cell,

$mergeCells

)

{


foreach(

$mergeCells as $range

)

{


list(

$start,

$end

)

=

explode(

":",

$range

);




[$cellCol,$cellRow]

=

Coordinate::coordinateFromString(

$cell

);




[$startCol,$startRow]

=

Coordinate::coordinateFromString(

$start

);




[$endCol,$endRow]

=

Coordinate::coordinateFromString(

$end

);




$cellCol = Coordinate::columnIndexFromString(

$cellCol

);


$startCol = Coordinate::columnIndexFromString(

$startCol

);


$endCol = Coordinate::columnIndexFromString(

$endCol

);




if(

$cellRow >= $startRow

&&

$cellRow <= $endRow


&&


$cellCol >= $startCol


&&


$cellCol <= $endCol

)

{


return $start;


}



}


return $cell;



}




foreach(

$mapping as $field=>$cell

)

{


if(

!isset(

$d[$field]

)

)

{

continue;

}



$targetCell = masterCellForTarget(

$cell,

$mergeCells

);



$value = $d[$field];




if(

$field=="tanggal_phk"

||

$field=="tanggal_surat_lphk"

)

{


if(

!empty(

$value

)

)

{


$value = date(

"d-m-Y",

strtotime(

$value

)

);


}



}





$sheet->setCellValueExplicit(

$targetCell,

(string)$value,

DataType::TYPE_STRING

);



}




$filename = str_replace(

" ",

"_",

$d['nama_pekerja']

);


$filename .= ".xlsx";




header(

'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

);



header(

'Content-Disposition: attachment; filename="'.$filename.'"'

);



header(

'Cache-Control: max-age=0'

);




$writer = IOFactory::createWriter(

$spreadsheet,

'Xlsx'

);



$writer->save(

'php://output'

);



exit;



?>