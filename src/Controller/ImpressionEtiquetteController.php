<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\EtiquetteFormatRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorPNG;

#[Route('/caisse/impressions')]
class ImpressionEtiquetteController extends AbstractController
{
    #[Route('/etiquettes', name: 'app_caisse_impressions_etiquettes')]
    public function index(
        ArticleRepository $articleRepository,
        EtiquetteFormatRepository $etiquetteFormatRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $etiquetteFormat = $etiquetteFormatRepository->findOneBy([]);
        
        $queryBuilder = $articleRepository->createQueryBuilder('a')
            ->orderBy('a.nom', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20 // Limit per page
        );

        return $this->render('caisse/impressions/etiquettes.html.twig', [
            'pagination' => $pagination,
            'format' => $etiquetteFormat
        ]);
    }

    #[Route('/etiquettes/print', name: 'app_caisse_impressions_etiquettes_print', methods: ['POST'])]
    public function print(
        Request $request,
        ArticleRepository $articleRepository,
        EtiquetteFormatRepository $etiquetteFormatRepository
    ): Response {
        $ids = $request->request->all('articles');
        $quantities = $request->request->all('quantities'); // Array of quantities [id => qty]
        $skippedIndices = $request->request->all('skipped_indices'); // Array of indices to skip (0-based)
        
        if (empty($ids)) {
            $this->addFlash('warning', 'Aucun article sélectionné.');
            return $this->redirectToRoute('app_caisse_impressions_etiquettes');
        }

        $etiquetteFormat = $etiquetteFormatRepository->findOneBy([]);
        if (!$etiquetteFormat) {
            $this->addFlash('error', 'Aucun format d\'étiquette configuré. Veuillez le configurer dans les paramètres.');
            return $this->redirectToRoute('app_caisse_impressions_etiquettes');
        }

        $articles = $articleRepository->findBy(['id' => $ids]);
        // Re-index by ID for easier access
        $articlesById = [];
        foreach ($articles as $article) {
            $articlesById[$article->getId()] = $article;
        }
        
        // Generate barcodes
        $generator = new BarcodeGeneratorPNG();
        $articlesData = [];

        // Loop through submitted IDs to maintain order and quantity
        foreach ($ids as $id) {
            if (!isset($articlesById[$id])) continue;
            
            $article = $articlesById[$id];
            $qty = isset($quantities[$id]) ? (int)$quantities[$id] : 1;
            
            if ($qty <= 0) continue;

            $code = $article->getGencod();
            if (!$code) {
                $barcodeData = null;
            } else {
                try {
                    $type = (ctype_digit($code) && (strlen($code) == 12 || strlen($code) == 13)) 
                            ? $generator::TYPE_EAN_13 
                            : $generator::TYPE_CODE_128;
                    
                    $barcodeData = base64_encode($generator->getBarcode($code, $type));
                } catch (\Exception $e) {
                    $barcodeData = null;
                }
            }

            // Get price from first size
            $price = 0;
            $tailles = $article->getTailles();
            if (!empty($tailles) && isset($tailles[0]['prix'])) {
                $price = $tailles[0]['prix'];
            }

            $articleData = [
                'nom' => $article->getNom(),
                'prix' => $price,
                'code' => $code,
                'barcode' => $barcodeData
            ];

            // Add article multiple times based on quantity
            for ($i = 0; $i < $qty; $i++) {
                $articlesData[] = $articleData;
            }
        }


        // Merge skipped indices with articles
        $finalEtiquettes = [];
        $articleIndex = 0;
        $maxSlots = count($articlesData) + count($skippedIndices); 
        // We need to iterate enough slots to fit all articles. 
        // Simplistic approach: just keep going until we've placed all articles.
        
        $currentSlot = 0;
        while ($articleIndex < count($articlesData)) {
            if (in_array($currentSlot, $skippedIndices)) {
                $finalEtiquettes[] = null; // Empty slot
            } else {
                $finalEtiquettes[] = $articlesData[$articleIndex];
                $articleIndex++;
            }
            $currentSlot++;
        }

        // Calculate columns for table layout
        $pageWidth = 21.0; // A4 width in cm
        $availableWidth = $pageWidth - $etiquetteFormat->getMargeGauche() - $etiquetteFormat->getMargeDroite();
        $cols = 1;
        if ($etiquetteFormat->getLargeur() > 0 && $etiquetteFormat->getLargeur() <= $availableWidth) {
            $cols = 1 + floor(($availableWidth - $etiquetteFormat->getLargeur()) / ($etiquetteFormat->getLargeur() + $etiquetteFormat->getDistanceHorizontale()));
        }

        // Generate PDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->setIsRemoteEnabled(true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->renderView('caisse/impressions/pdf_etiquettes.html.twig', [
            'etiquettes' => $finalEtiquettes,
            'format' => $etiquetteFormat,
            'cols' => $cols
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="etiquettes.pdf"',
        ]);
    }
}
