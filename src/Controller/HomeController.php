<?php

namespace App\Controller;

use App\Service\CategoryService;
use App\Service\ExcelService;
use App\Service\IcmlService;
use App\Type\FileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

class HomeController extends AbstractController
{
    public function template()
    {
        $excelService = new ExcelService();
        $content = $excelService->generateXlsxTemplate();

        return new Response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            "Content-Disposition: attachment; filename=template.xlsx"
        ]);
    }

    public function index(Request $request, SluggerInterface $slugger)
    {
        $form = $this->createForm(FileType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $excelService = new ExcelService();

            $filesDir = '../public/files';
            $excelService->clearDirectory($filesDir);

            $uploadedFile     = $form->get('file')->getData();
            $fileType         = explode('.', $uploadedFile->getClientOriginalName())[1];
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename     = $slugger->slug($originalFilename);
            $newFilename      = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

            $uploadedFile->move($filesDir, $newFilename);

            $categoryService = new CategoryService();
            $icmlService     = new IcmlService($excelService, $categoryService);

            if ($fileType  === 'xlsx' || $fileType === 'xml') {
                $icmlName = $icmlService->generateIcml($filesDir . '/'. $newFilename);
            } elseif ($fileType === 'csv') {
                $icmlName = $icmlService->generateIcml($filesDir . '/'. $newFilename, true);
            }

            return $this->render('upload.html.twig', [
                'form' => $form->createView(),
                'icml_file' => $icmlName
            ]);
        }

        return $this->render('upload.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
