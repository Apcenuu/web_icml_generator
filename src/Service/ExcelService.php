<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ExcelService
{
    public function readFile($filename)
    {
        $reader = new Xlsx();
        $spreadsheet = $reader->load($filename);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        unset($rows[0]);
        return $rows;
    }
}
