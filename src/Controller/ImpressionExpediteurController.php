<?php

namespace App\Controller;

use App\Repository\EtiquetteFormatRepository;
use App\Repository\SocieteRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/impressions/expediteur')]
class ImpressionExpediteurController extends AbstractController
{
    #[Route('/', name: 'app_caisse_impressions_expediteur')]
    public function index(
        EtiquetteFormatRepository $etiquetteFormatRepository
    ): Response {
        $etiquetteFormat = $etiquetteFormatRepository->findOneBy(['type' => 'address']);
        
        return $this->render('caisse/impressions/expediteur.html.twig', [
            'format' => $etiquetteFormat,
        ]);
    }

    #[Route('/print', name: 'app_caisse_impressions_expediteur_print', methods: ['POST'])]
    public function print(
        Request $request,
        EtiquetteFormatRepository $etiquetteFormatRepository,
        SocieteRepository $societeRepository
    ): Response {
        $quantity = (int) $request->request->get('quantity', 0);
        $skippedIndices = json_decode($request->request->get('skipped_indices', '[]'), true);
        
        if ($quantity <= 0) {
            $this->addFlash('warning', 'Quantité invalide.');
            return $this->redirectToRoute('app_caisse_impressions_expediteur');
        }

        $etiquetteFormat = $etiquetteFormatRepository->findOneBy(['type' => 'address']);
        if (!$etiquetteFormat) {
            $this->addFlash('error', 'Format d\'étiquette adresse non configuré.');
            return $this->redirectToRoute('app_caisse_impressions_expediteur');
        }

        $societe = $societeRepository->findOneBy([]);
        if (!$societe) {
            $this->addFlash('error', 'Configuration société manquante.');
             return $this->redirectToRoute('app_caisse_impressions_expediteur');
        }

        $etiquettes = [];
        $currentSlotIndex = 0;
        $printedCount = 0;
        
        // Fill slots
        while ($printedCount < $quantity) {
            if (in_array($currentSlotIndex, $skippedIndices)) {
                $etiquettes[] = null; // Used slot
            } else {
                $etiquettes[] = $societe; // Pass societe object to be rendered
                $printedCount++;
            }
            $currentSlotIndex++;
        }
        
        // Calculate columns for PDF layout
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
        
        // Prepare Logo
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/assets/img/logo_petit_noir.png';
        $logoSrc = null;
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoSrc = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $html = $this->renderView('caisse/impressions/pdf_etiquettes_expediteur.html.twig', [
            'etiquettes' => $etiquettes,
            'format' => $etiquetteFormat,
            'cols' => $cols,
            'societe' => $societe,
            'logoSrc' => $logoSrc
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="etiquettes_expediteur.pdf"',
        ]);
    }
}
