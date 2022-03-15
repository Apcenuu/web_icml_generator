<?php

namespace App\Controller;

use App\Service\CategoryService;
use App\Service\ExcelService;
use App\Service\IcmlService;
use App\Service\ApiService;
use App\Type\FileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

class HomeController extends AbstractController
{
    public function icml()
    {
        $excelService = new ExcelService();
        $categoryService = new CategoryService();
        $icmlService = new IcmlService($excelService, $categoryService);
        $icmlData = $icmlService->parseXmlToArray('xml/mr_bliss.xml');
        foreach ($icmlData['yml_catalog']['shop']['offers']['offer'] as $key => $offer) {
            unset($icmlData['yml_catalog']['shop']['offers']['offer'][$key]['url']);

        }

        $icmlService->updateIcml($icmlData['yml_catalog']['shop']);
        die;
    }

    public function downloadOrders()
    {
        $excelService = new ExcelService();
        $apiUrl = $this->getParameter('app.api_url');
        $apiKey = $this->getParameter('app.api_key');
        $apiService = new ApiService($apiUrl, $apiKey);
        $orders = $apiService->getOrders(100);
        $products = $apiService->getProductsByOrders($orders);

        $content = $excelService->generateXlsx($products, $orders);

        return new Response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            "Content-Disposition: attachment; filename=template.xlsx"
        ]);
    }

    public function download()
    {
        $apiUrl = $this->getParameter('app.api_url');
        $apiKey = $this->getParameter('app.api_key');
        $apiService = new ApiService($apiUrl, $apiKey);
        $products = $apiService->getProducts();
        $excelService = new ExcelService();
        $categoryService = new CategoryService();
        $icmlService = new IcmlService($excelService, $categoryService, $apiService);
        $icmlService->generateIcmlByProductArray($products);
        die;
    }


    public function index(Request $request, SluggerInterface $slugger)
    {
        $form = $this->createForm(FileType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $excelService = new ExcelService();

            $xlsxDir = '../public/xlsx';
            $excelService->clearDirectory($xlsxDir);

            $uploadedFile = $form->get('file')->getData();
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

            $uploadedFile->move($xlsxDir, $newFilename);

//            return $this->redirectToRoute('structure', [
//                'file' => $newFilename
//            ]);

            $excelService = new ExcelService();
            $categoryService = new CategoryService();
            $icmlService = new IcmlService($excelService, $categoryService);
            $xlsxDir = '../public/xlsx';
            $fileName = $xlsxDir . '/'. $newFilename;
            $rows = $excelService->readFile($fileName, 10);
            $headers = array_shift($rows);
            $icmlName = $icmlService->generateIcmlByFile($fileName, $safeFilename, true);

            return $this->render('upload.html.twig', [
                'form' => $form->createView(),
                'headers' => $headers,
                'rows' => $rows,
                'icml_file' => $icmlName
            ]);
        }

        return $this->render('upload.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
