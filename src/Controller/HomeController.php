<?php

namespace App\Controller;

use App\Service\CategoryService;
use App\Service\ExcelService;
use App\Service\IcmlService;
use App\Type\FileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

class HomeController extends AbstractController
{
    public function index(Request $request, SluggerInterface $slugger)
    {
        $form = $this->createForm(FileType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $uploadedFile = $form->get('file')->getData();
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();
            $uploadedFile->move(
                '../../PrestaShop176/xlsx',
                $newFilename
            );

            $excelService = new ExcelService();
            $categoryService = new CategoryService();
            $icmlService = new IcmlService($excelService, $categoryService);
            $icmlName = $icmlService->generateIcml('../../PrestaShop176/xlsx/'. $newFilename);

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
