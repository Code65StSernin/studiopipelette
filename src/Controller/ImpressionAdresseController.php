<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\EtiquetteFormatRepository;
use App\Repository\OrderRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/impressions/adresses')]
class ImpressionAdresseController extends AbstractController
{
    #[Route('/', name: 'app_caisse_impressions_adresses')]
    public function index(
        EtiquetteFormatRepository $etiquetteFormatRepository,
        Request $request,
        OrderRepository $orderRepository
    ): Response {
        $etiquetteFormat = $etiquetteFormatRepository->findOneBy(['type' => 'address']);
        
        // Fetch all orders for "Lettre Suivie" or orders that need printing
        // Sort: Not printed first, then printed. Within groups, by date desc.
        // We probably want recent orders. Let's limit to recent 100 or something reasonable.
        
        $qb = $orderRepository->createQueryBuilder('o')
            ->orderBy('o.addressLabelPrintedAt', 'ASC') // NULLs first usually in MySQL/Postgres but safe to be explicit if needed
            ->addOrderBy('o.createdAt', 'DESC')
            ->where("o.status = 'paid'")
            ->setMaxResults(100);
            
        // Note: NULL sorting behavior can vary. In Doctrine/MySQL, ASC puts NULL first usually.
        // Let's ensure separation in logic if needed, but simple sort might suffice.
        // Also user wants "priority" for non-printed.
        
        $allOrders = $qb->getQuery()->getResult();
        
        // Separate for easier UI handling if desired, or pass as is.
        // Let's pass as is, the template can check addressLabelPrintedAt.

        // If an order ID is passed, we might want to ensure it's in the list or handle it
        $preselectedOrderId = $request->query->get('order');

        return $this->render('caisse/impressions/adresses.html.twig', [
            'format' => $etiquetteFormat,
            'orders' => $allOrders,
            'preselectedOrderId' => $preselectedOrderId
        ]);
    }

    #[Route('/print', name: 'app_caisse_impressions_adresses_print', methods: ['POST'])]
    public function print(
        Request $request,
        OrderRepository $orderRepository,
        EtiquetteFormatRepository $etiquetteFormatRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $orderIds = $request->request->all('order_ids'); // Array of IDs
        $skippedIndices = json_decode($request->request->get('skipped_indices', '[]'), true);
        
        if (empty($orderIds)) {
            $this->addFlash('warning', 'Aucune commande sélectionnée.');
            return $this->redirectToRoute('app_caisse_impressions_adresses');
        }

        $etiquetteFormat = $etiquetteFormatRepository->findOneBy(['type' => 'address']);
        if (!$etiquetteFormat) {
            $this->addFlash('error', 'Format d\'étiquette adresse non configuré.');
            return $this->redirectToRoute('app_caisse_impressions_adresses');
        }

        $orders = $orderRepository->findBy(['id' => $orderIds]);
        // Sort orders to match the order of IDs passed if important? 
        // Or just print them in retrieval order. The user selects them.
        // Let's try to respect the user selection order if possible, but finding by IDs returns arbitrary order.
        // We can reorder.
        $ordersById = [];
        foreach ($orders as $o) {
            $ordersById[$o->getId()] = $o;
        }
        
        $sortedOrders = [];
        foreach ($orderIds as $id) {
            if (isset($ordersById[$id])) {
                $sortedOrders[] = $ordersById[$id];
            }
        }

        $etiquettes = [];
        // Fill initial skipped slots
        // Find the first available slot index
        // Logic: we have a sequence of slots. Skipped indices are "holes".
        // But usually, skipped indices means "these slots on the sheet are already used".
        // So we just need to skip them in the linear sequence of filling.
        
        // Example: skipped [0, 1, 4].
        // Slot 0: Empty
        // Slot 1: Empty
        // Slot 2: Order 1
        // Slot 3: Order 2
        // Slot 4: Empty
        // Slot 5: Order 3
        
        // So we iterate through slots 0...N. If slot index is in skipped, we put null.
        // Else we put next order.
        
        $currentSlotIndex = 0;
        $orderIndex = 0;
        
        // We need to place all orders.
        while ($orderIndex < count($sortedOrders)) {
            if (in_array($currentSlotIndex, $skippedIndices)) {
                $etiquettes[] = null; // Used slot
            } else {
                $order = $sortedOrders[$orderIndex];
                
                // Prepare Address Data
                $facture = $order->getFacture();
                $addressText = null;
                $recipientName = null;

                if ($facture) {
                    $recipientName = $facture->getClientPrenom() . ' ' . $facture->getClientNom();
                    $ligne1 = trim((string) $facture->getClientAdresse());
                    $ligne2Parts = [];
                    
                    $zipCity = trim(($facture->getClientCodePostal() ?? '') . ' ' . ($facture->getClientVille() ?? ''));
                    
                    // Avoid duplication: only add Zip/City if not already present in the address (case insensitive)
                    if (!empty($zipCity) && stripos($ligne1, $zipCity) === false) {
                        $ligne2Parts[] = $zipCity;
                    }
                    
                    if ($facture->getClientPays()) {
                        $ligne2Parts[] = $facture->getClientPays();
                    }
                    $ligne2 = implode(' - ', array_filter($ligne2Parts));
                    $addressText = trim($ligne1 . "\n" . $ligne2);
                }

                if (empty($addressText) && ($user = $order->getUser())) {
                    $recipientName = $user->getPrenom() . ' ' . $user->getNom();
                    foreach ($user->getAddresses() as $addr) {
                        if ($addr->isDefault()) {
                             $addressText = $addr->getStreetNumber() . ' ' . $addr->getStreet() . "\n";
                             $addressText .= $addr->getPostalCode() . ' ' . $addr->getCity();
                             break;
                        }
                    }
                }

                if ($addressText) {
                    $etiquettes[] = [
                        'name' => $recipientName,
                        'address' => $addressText
                    ];
                    
                    // Mark as printed
                    $order->setAddressLabelPrintedAt(new \DateTime());
                } else {
                    // Order has no address? Skip it or print error placeholder?
                    // Let's print a placeholder to not shift everything
                    $etiquettes[] = [
                        'name' => 'ERREUR ADRESSE',
                        'address' => "Commande #" . $order->getId()
                    ];
                }
                
                $orderIndex++;
            }
            $currentSlotIndex++;
        }
        
        $entityManager->flush();

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
        
        $html = $this->renderView('caisse/impressions/pdf_etiquettes_adresse.html.twig', [
            'etiquettes' => $etiquettes,
            'format' => $etiquetteFormat,
            'cols' => $cols
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="adresses.pdf"',
        ]);
    }
}
