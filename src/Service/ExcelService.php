<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Worksheet;

class ExcelService
{
    const MAX_FILES = 1;

    public function readFile($filename)
    {
        $reader = new Xlsx();
        $spreadsheet = $reader->load($filename);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        unset($rows[0]);
        return $rows;
    }

    public function clearDirectory($directory)
    {
        $files = array_filter(scandir($directory), function ($value, $key) {
            $fileExtensionArray = explode('.', $value);
            $fileExtension = end($fileExtensionArray);
            if ($fileExtension === 'xlsx' || $fileExtension === 'xml') {
                return true;
            }
            return false;
        }, ARRAY_FILTER_USE_BOTH);
        $countFiles = count($files);

        while ($countFiles >= self::MAX_FILES) {
            unlink($directory . '/'. array_shift($files));
            $countFiles = count($files);
        }
    }

    public function generateXlsxTemplate()
    {
        $filename = "template.xlsx";
        $titles = [
            0 => 0,
            1 => 'article',
            2 => 'name',
            3 => 0,
            4 => 0,
            5 => 'price',
            6 => 'picture'
        ];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($titles);
        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save($filename);

        return file_get_contents($filename);
    }
}
