<?php

namespace App\Service;

use App\Service\RetailcrmIcml;

class IcmlService
{
    private ExcelService $excelService;
    private CategoryService $categoryService;

    public function __construct(ExcelService $excelService, CategoryService $categoryService)
    {
        $this->excelService = $excelService;
        $this->categoryService = $categoryService;
    }

    public function generateIcml($fileName)
    {
        $rows = $this->excelService->readFile($fileName);

        $offers = [];
        $categories = [
            [
                'id' => 1,
                'name' => 'Main'
            ]
        ];

        foreach ($rows as $key => $row) {

            if ($this->getPrice($this->getValueFromRow($row,5)) == 0) continue;

            foreach ($row as $cellKey => $cell) {
                if (is_null($cell)) {
                    unset($row[$cellKey]);
                }
            }


            $offer = [
                'id' => uniqid(),
//                'description' => $row[2],
                'categoryId' => [1],
                'productId' => uniqid(),
//                'parent' => htmlspecialchars(utf8_encode(array_shift($offerCategory))),
//                'category' => $offerCategory,
                'productName'=> ucfirst($this->getValueFromRow($row,2)),
                'quantity' => 1,
                'name' => ucfirst($this->getValueFromRow($row,2)),
                'price' => $this->getPrice($this->getValueFromRow($row,5)),
//                'purchasePrice' => $this->getPrice($this->getValueFromRow($row,10)),
                'article' => $this->getValueFromRow($row,1, true),
                'url' => 'https://google.com',
//                'color' => ucfirst($this->getValueFromRow($row,7)),
//                'size' => $this->getValueFromRow($row,8),
//                'vendor' => ucfirst($this->getValueFromRow($row, 3)),
                'picture' => $this->getValueFromRow($row,6)
            ];

            $offers[] = $offer;

        }

        $resultFile = 'MerKomuna';
        $xmlDir = 'xml/';
        $this->excelService->clearDirectory($xmlDir);

        $icml = new RetailcrmIcml('MerKomuna', $xmlDir . $resultFile);
        $icml->generate($categories, $offers);

        return $resultFile;
    }

    private function getPrice($price)
    {
        if (is_null($price)) {
            return 0;
        }

        return (float) trim(str_replace('$', '', $price));
    }

    private function resultFile(): string
    {
        return uniqid() . '.xml';
    }

    private function getValueFromRow($row, $index, bool $int = false)
    {
        if (!isset($row[$index])) {
            if ($int) {
                return 0;
            }
            return '';
        }
        if ($int) {
            return (int) $row[$index];
        }
        return (string) $row[$index];
    }
}
