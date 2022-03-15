<?php

namespace App\Service;

use App\Service\RetailcrmIcml;
use Psr\Container\ContainerInterface;

class IcmlService
{
    private ExcelService $excelService;
    private CategoryService $categoryService;
    private ContainerInterface $container;
    private ApiService $apiService;

    public function __construct(ExcelService $excelService, CategoryService $categoryService, ApiService $apiService = null)
    {
        $this->excelService = $excelService;
        $this->categoryService = $categoryService;
        if ($apiService) {
            $this->apiService = $apiService;
        }

    }

    public function parseXmlToArray(string $filename)
    {
        $stream = fopen($filename, 'r');
        $parser = xml_parser_create();
        $xmlData = '';

        while (($data = fread($stream, 16384))) {
            $xmlData .= $data;
            xml_parse($parser, $data); // разобрать текущую часть
        }
        xml_parse($parser, '', true); // завершить разбор
        xml_parser_free($parser);
        fclose($stream);

        $xml   = simplexml_load_string($xmlData);
        $array = json_decode(json_encode((array) $xml), true);
        $array = array($xml->getName() => $array);

        return $array;
    }

    public function updateIcml(array $icmlData)
    {
        $offers = $icmlData['offers']['offer'];

        foreach ($offers as $key => $offer) {
            if (isset($offer['@attributes'])) {
                $offers[$key]['id'] = $offer['@attributes']['id'];
                $offers[$key]['productId'] = $offer['@attributes']['productId'];
                $offers[$key]['quantity'] = $offer['@attributes']['quantity'];
                unset($offers[$key]['@attributes']);

            }
            if (isset($offer['param'])) {
                $offers[$key]['article'] = $offer['param'];
                unset($offers[$key]['param']);
            }
            $offers[$key]['categoryId'] = [$offers[$key]['categoryId']];
        }


        $categories = $icmlData['categories']['category'];
        foreach ($categories as $key => $category) {
            if (isset($category['@attributes'])) {

                $categories[$key]['id'] = $category['@attributes']['id'];
                if (isset($category['@attributes']['parentId'])) {
                    $categories[$key]['parentId'] = $category['@attributes']['parentId'];
                }

                $categories[$key]['name'] = htmlspecialchars($category['name']);

                unset($categories[$key]['@attributes']);

            }
        }

        $icml = new RetailcrmIcml('MerKomuna', 'xml/mr_bliss_new.xml');
        $icml->generate($categories, $offers);

    }

    public function generateIcmlByProductArray(array $products)
    {
        $offers = [];
        $categories = [];


        foreach ($products as $product) {

            $offer = (array) array_shift($product->offers);
            $categoryId = array_shift($product->groups)->id;

            $havingParentCategory = array_filter($categories, function ($category) use ($categoryId) {
                if ($category['id'] == $categoryId) {
                    return true;
                }
                return false;
            });
            if (count($havingParentCategory) == 0) {
                $categories[] = $this->apiService->getProductGroupById($categoryId);
            }

            $offer['productId'] = $product->id;
            $offer['categoryId'] = [$categoryId];
            $offer['price'] = 0;
            $offer['picture'] = $product->imageUrl;
            $offer['url'] = $product->url;

            $offers[] = $offer;
        }

        foreach ($categories as $key => $category) {
            $categoryParent = $category['parentId'];

            if ($categoryParent !== null) {
                $categories[] = $this->apiService->getProductGroupById($categoryParent);
            }
        }

        $resultFile = 'MerKomuna.xml';
        $xmlDir = 'xml/';
        $this->excelService->clearDirectory($xmlDir);

        $icml = new RetailcrmIcml('MerKomuna', $xmlDir . $resultFile);
        $icml->generate($categories, $offers);

    }

    public function generateIcmlByFile($fileName, $shop, $withCategories = false)
    {
        $rows = $this->excelService->readFile($fileName);

        $offers = [];

        if ($withCategories) {
            $categories = [];
            $categoryIdCounter = 0;
        } else {
            $categories = [
                [
                    'id' => 1,
                    'name' => 'Main'
                ]
            ];
            $categoryIdCounter = 1;
        }

        $headers = array_shift($rows);

        foreach ($rows as $key => $row) {

            if ($withCategories) {
                $havingRowCategory = array_filter($categories, function ($category) use ($row) {
                    if ($category['name'] == $row[2] || $category['name'] == $row[3]) {
                        return true;
                    }
                    return false;
                });

                if (count($havingRowCategory) == 0 && $row[0] !== null) {
                    $categoryIdCounter++;
                    $categories[] = [
                        'id' => $categoryIdCounter,
                        'name' => $row[2]
                    ];

                    $categoryIdCounter++;
                    $parentCategory = $categoryIdCounter-1;
                    $categories[] = [
                        'id' => $categoryIdCounter,
                        'parentId' => $parentCategory,
                        'name' => $row[3]
                    ];

                }
            }

            $productName = ucfirst($this->getValueFromRow($row,1));
            $name = $this->getValueFromRow($row,1);
            $productId = uniqid();
            if (strlen($productName) == 0) {
                continue;
            }
            $price = $this->getPrice($this->getValueFromRow($row,4));
            $offer = [
                'id' => $productId,
//                'description' => $row[2],
                'categoryId' => [$categoryIdCounter],
                'productId' => $productId,
//                'parent' => htmlspecialchars(utf8_encode(array_shift($offerCategory))),
//                'category' => $offerCategory,
                'productName'=> $productName,
                'quantity' => $this->getValueFromRow($row,3),
                'name' => $name,
                'price' => $price,
//                'purchasePrice' => $this->getPrice($this->getValueFromRow($row,4)),
                'article' => $this->getValueFromRow($row,0),
//                'url' => $this->getValueFromRow($row,7),
//                'color' => ucfirst($this->getValueFromRow($row,7)),
//                'size' => $this->getValueFromRow($row,8),
//                'vendor' => ucfirst($this->getValueFromRow($row, 3)),
//                'picture' => $this->getValueFromRow($row,6),
//                'description' => $this->getValueFromRow($row, 5) . ' ' . $this->getValueFromRow($row, 6)
            ];
//            dump($row, $offer);die;
            if (strlen($offer['name']) > 0) {
                $offers[] = $offer;
            }

//
        }
//        dump($categories);die;
        $resultFile = $shop . '.xml';
        $xmlDir = 'xml/';
        $this->excelService->clearDirectory($xmlDir);

        $icml = new RetailcrmIcml(ucfirst($shop), $xmlDir . $resultFile);
        $icml->generate($categories, $offers);

        return $resultFile;
    }

    private function getPrice($price, $currency = 'S/')
    {
        if (is_null($price)) {
            return 0;
        }

        return (float) trim(str_replace($currency, '', $price));
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
