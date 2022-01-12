<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ExcelService
{
    const MAX_FILES = 10;

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

        if ($countFiles >= self::MAX_FILES) {
            foreach ($files as $file) {
                unlink($directory . '/'. $file);
            }
        }

    }
}
