<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Facture;
use App\Entity\LigneFacture;
use App\Repository\OrderRepository;
use App\Repository\FactureRepository;
use App\Service\PanierService;
use App\Service\SocieteConfig;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FacturePdfGenerator;
use App\Service\OrderMailer;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


#[IsGranted('ROLE_USER')]
class PaiementController extends AbstractController
{
    public function __construct(
        private PanierService $panierService,
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository,
        private FactureRepository $factureRepository,
        private SocieteConfig $societeConfig,
    ) {}

    #[Route('/paiement', name: 'app_paiement', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $panier = $this->panierService->getPanier();

        if ($panier->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide');
            return $this->redirectToRoute('app_panier');
        }

        $livraison = $request->getSession()->get('livraison');
        if (!$livraison || !isset($livraison['mode'])) {
            $this->addFlash('warning', 'Veuillez choisir un mode de livraison avant de payer.');
            return $this->redirectToRoute('app_livraison_choix');
        }

        $fraisLivraison = $livraison['mode'] === 'domicile' ? 5.90 : 0.00;

        $totalProduits = (float) $panier->getTotalTTC();
        $remisePourcentage = $panier->getCodePromoPourcentage() ?? 0.0;
        $montantRemise = $remisePourcentage > 0 ? $totalProduits * ($remisePourcentage / 100) : 0.0;
        $totalProduitsApresRemise = max(0, $totalProduits - $montantRemise);

        $total = $totalProduitsApresRemise + $fraisLivraison;

        return $this->render('paiement/index.html.twig', [
            'panier' => $panier,
            'livraison' => $livraison,
            'fraisLivraison' => $fraisLivraison,
            'total' => $total,
            'totalProduits' => $totalProduits,
            'remisePourcentage' => $remisePourcentage,
            'montantRemise' => $montantRemise,
            'totalProduitsApresRemise' => $totalProduitsApresRemise,
        ]);
    }

    #[Route('/paiement/checkout', name: 'app_paiement_checkout', methods: ['POST'])]
    public function checkout(Request $request): Response
    {
        $panier = $this->panierService->getPanier();

        if ($panier->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide');
            return $this->redirectToRoute('app_panier');
        }

        $livraison = $request->getSession()->get('livraison');
        if (!$livraison || !isset($livraison['mode'])) {
            $this->addFlash('warning', 'Veuillez choisir un mode de livraison avant de payer.');
            return $this->redirectToRoute('app_livraison_choix');
        }

        $fraisLivraison = ($livraison['mode'] === 'domicile') ? 5.90 : 0.00;

        $totalProduits = (float) $panier->getTotalTTC();
        $remisePourcentage = $panier->getCodePromoPourcentage() ?? 0.0;
        $montantRemise = $remisePourcentage > 0 ? $totalProduits * ($remisePourcentage / 100) : 0.0;
        $totalProduitsApresRemise = max(0, $totalProduits - $montantRemise);

        $total = $totalProduitsApresRemise + $fraisLivraison;

        $amountProduits = (int) round($totalProduitsApresRemise * 100);
        $amountLivraison = (int) round($fraisLivraison * 100);
        $amountTotal = (int) round($total * 100);

        $user = $this->getUser();

        // 1) Créer la commande AVANT Stripe
        $order = new Order();
        $order->setUser($user);
        $order->setStatus(Order::STATUS_PENDING);
        $order->setAmountProductsCents($amountProduits);
        $order->setAmountShippingCents($amountLivraison);
        $order->setAmountTotalCents($amountTotal);
        $order->setShippingMode((string) $livraison['mode']);

        if ($livraison['mode'] === 'relais') {
            $order->setRelayId($livraison['point_relais_id'] ?? null);
            $order->setRelayName($livraison['point_relais_nom'] ?? null);
            $order->setRelayAddress($livraison['point_relais_adresse'] ?? null);
        }

        // Calcul du poids total de la commande à partir des articles du panier
        $poidsTotalKg = 0.0;
        foreach ($panier->getLignes() as $ligne) {
            $article  = $ligne->getArticle();
            $quantite = $ligne->getQuantite();
            $poidsArticle = $article->getPoidsKg() ?? 0.0;
            $poidsTotalKg += $poidsArticle * $quantite;
        }
        if ($poidsTotalKg > 0) {
            $order->setMondialRelayWeightKg($poidsTotalKg);
        }

        $this->em->persist($order);
        $this->em->flush(); // IMPORTANT : on récupère $order->getId()

        Stripe::setApiKey((string) $this->societeConfig->getStripeSecretKey());

        $successUrl = $this->generateUrl(
            'app_paiement_success',
            ['session_id' => '{CHECKOUT_SESSION_ID}'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );


        $cancelUrl = $this->generateUrl(
            'app_paiement_cancel',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $lineItems = [];

        $societeNom = $this->societeConfig->getNom() ?? "So'Sand";
        
        $lineItems[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => "Commande " . $societeNom,
                ],
                'unit_amount' => $amountProduits,
            ],
            'quantity' => 1,
        ];

        if ($amountLivraison > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => "Frais de livraison",
                    ],
                    'unit_amount' => $amountLivraison,
                ],
                'quantity' => 1,
            ];
        }

        // 2) Metadata Stripe (avec order_id !)
        $metadata = [
            'order_id' => (string) $order->getId(),
            'user_id' => (string) $user->getUserIdentifier(),
            'mode_livraison' => (string) $livraison['mode'],
        ];

        if ($livraison['mode'] === 'relais') {
            $metadata['relais_id'] = (string) ($livraison['point_relais_id'] ?? '');
            $metadata['relais_nom'] = (string) ($livraison['point_relais_nom'] ?? '');
        }

        $session = CheckoutSession::create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $user->getUserIdentifier(),
            'metadata' => $metadata,
        ]);

        // 3) Lier la session Stripe à la commande
        $order->setStripeCheckoutSessionId($session->id);
        $this->em->flush();

        $request->getSession()->set('stripe_checkout_session_id', $session->id);
        $request->getSession()->set('last_order_id', $order->getId());

        return $this->redirect($session->url);
    }


    // src/Controller/PaiementController.php

    #[Route('/paiement/success', name: 'app_paiement_success', methods: ['GET'])]
    public function success(Request $request, OrderMailer $orderMailer, FacturePdfGenerator $pdfGenerator): Response
    {
        $orderId = $request->getSession()->get('last_order_id');

        if (!$orderId) {
            return $this->redirectToRoute('app_home');
        }

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            return $this->redirectToRoute('app_home');
        }

        // Si le statut est encore en attente, le webhook n'a pas encore été traité
        // Comme Stripe nous redirige ici seulement après paiement réussi, on peut forcer le statut
        if ($order->getStatus() === Order::STATUS_PENDING) {
            $order->setStatus(Order::STATUS_PAID);
            $order->setPaidAt(new \DateTimeImmutable());
            $this->em->flush();
        }

        // Vérifier que le paiement est bien validé
        if ($order->getStatus() !== Order::STATUS_PAID) {
            return $this->redirectToRoute('app_home');
        }

        $panier = $this->panierService->getPanier();

        // Debug: vérifier le contenu du panier
        if ($panier->getLignes()->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier');
        }

        $this->em->beginTransaction();

        try {
            // 1) Décrémenter le stock
            foreach ($panier->getLignes() as $ligne) {
                $article  = $ligne->getArticle();
                $taille   = $ligne->getTaille();
                $quantite = $ligne->getQuantite();

                // Vérifier le stock disponible avant de décrémenter
                $stockDisponible = $article->getStockParTaille($taille);
                if ($stockDisponible === null || $stockDisponible < $quantite) {
                    throw new \RuntimeException(sprintf(
                        'Stock insuffisant pour l\'article "%s" (taille %s). Disponible: %d, demandé: %d',
                        $article->getNom(),
                        $taille,
                        $stockDisponible ?? 0,
                        $quantite
                    ));
                }

                $article->decrementerStock($taille, $quantite);
                $this->em->persist($article);
            }

            $this->em->flush(); // Flush intermédiaire pour le stock

            // 2) Créer la facture (avant de vider le panier)
            $facture = $this->creerFacture($order, $panier);

            // 3) Marquer le code promo comme utilisé par l'utilisateur le cas échéant
            $user = $order->getUser();
            if ($user && method_exists($panier, 'getCodePromo')) {
                $codePromo = $panier->getCodePromo();
                if ($codePromo) {
                    $user->addUsedPromoCode($codePromo);
                    $this->em->persist($user);
                }
            }

            // 4) Vider le panier
            $this->panierService->viderPanier();
            $this->em->commit();

        } catch (\Throwable $e) {
            $this->em->rollback();

            // Log détaillé de l'erreur
            error_log('Erreur lors de la finalisation de commande: ' . $e->getMessage());
            error_log('Fichier: ' . $e->getFile() . ' Ligne: ' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());

            $this->addFlash('danger', 'Une erreur est survenue lors de la finalisation de la commande: ' . $e->getMessage());
            return $this->redirectToRoute('app_home');
        }

        // Nettoyage session
        $request->getSession()->remove('last_order_id');
        $request->getSession()->remove('stripe_checkout_session_id');

        // Générer le PDF et l'envoyer par e-mail
        $pdfPath = $pdfGenerator->generate($facture);
        $orderMailer->sendOrderConfirmation($order, $facture, $pdfPath);

        $this->addFlash('success', 'Votre commande a bien été validée. Merci pour votre achat 🙏');

        $response = $this->redirectToRoute('app_home');
        // Nettoyer aussi le cookie de panier côté navigateur
        $this->panierService->clearSessionCookie($response);

        return $response;
    }


    #[Route('/paiement/cancel', name: 'app_paiement_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Paiement annulé.');
        return $this->redirectToRoute('app_paiement');
    }

    #[Route('/facture/{id}/pdf', name: 'app_facture_pdf', methods: ['GET'])]
    public function downloadFacturePdf(Facture $facture, FacturePdfGenerator $pdfGenerator): BinaryFileResponse
    {
        // Sécurité : on vérifie que l'utilisateur connecté est bien le propriétaire de la facture
        if ($this->getUser() !== $facture->getOrder()->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette facture.');
        }

        // Générer le chemin du fichier PDF à la volée
        $pdfPath = $pdfGenerator->generate($facture);

        // Créer une réponse pour afficher le PDF dans le navigateur (inline)
        $response = new BinaryFileResponse($pdfPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE, // Ouvre le PDF dans le navigateur
            'facture-' . $facture->getNumero() . '.pdf'
        );
        
        // Important : demander à Symfony de supprimer le fichier temporaire après l'envoi de la réponse
        $response->deleteFileAfterSend(true);

        return $response;
    }


    /**
     * Crée la facture pour la commande
     */
    private function creerFacture(Order $order, $panier): Facture
    {
        $user = $order->getUser();

        // Créer la facture
        $facture = new Facture();
        $facture->setOrder($order);
        $facture->setClientNom($user->getNom());
        $facture->setClientPrenom($user->getPrenom());
        $facture->setClientEmail($user->getEmail());
        $facture->setModeLivraison($order->getShippingMode());
        $facture->setFraisLivraison($order->getAmountShippingCents());
        $facture->setTotalTTC($order->getAmountTotalCents());

        if (method_exists($panier, 'getCodePromoPourcentage') && $panier->getCodePromoPourcentage() !== null) {
            $facture->setRemisePourcentage((float) $panier->getCodePromoPourcentage());
        }

        // Adresse de livraison figée sur la facture (pour les domiciliations)
        if ($order->getShippingMode() === 'domicile') {
            $adresseLivraison = null;
            $cp = null;
            $ville = null;

            // On tente de récupérer l'adresse par défaut de l'utilisateur
            $addresses = $user->getAddresses();
            $default = null;
            foreach ($addresses as $addr) {
                if ($addr->isDefault()) {
                    $default = $addr;
                    break;
                }
            }
            if (!$default && count($addresses) > 0) {
                $first = $addresses->first();
                $default = $first !== false ? $first : null;
            }

            if ($default) {
                $ligne1 = trim($default->getStreetNumber() . ' ' . $default->getStreet());
                $parts2 = [];
                if ($default->getComplement()) {
                    $parts2[] = $default->getComplement();
                }
                $parts2[] = trim($default->getPostalCode() . ' ' . $default->getCity());

                $adresseLivraison = $ligne1;
                if (!empty($parts2)) {
                    $adresseLivraison .= "\n" . implode(' - ', $parts2);
                }

                $cp = $default->getPostalCode();
                $ville = $default->getCity();
            }

            // On stocke ce qu'on a pu reconstruire (même partiel) sur la facture
            if ($adresseLivraison) {
                $facture->setClientAdresse($adresseLivraison);
            }
            if ($cp) {
                $facture->setClientCodePostal($cp);
            }
            if ($ville) {
                $facture->setClientVille($ville);
            }
        }

        // Obtenir le prochain numéro séquentiel pour le mois en cours
        // Note: Cette méthode doit être créée dans votre FactureRepository
        $sequentialNumber = $this->factureRepository->getNextSequentialNumberForCurrentMonth();

        // Générer le numéro de facture avec le numéro séquentiel
        // Note: Assurez-vous que les méthodes generateNumero et setNumero existent bien sur votre entité Facture
        // (ce que vous aviez fait initialement)
        $numero = $facture->generateNumero($sequentialNumber);
        $facture->setNumero($numero);

        // Créer les lignes de facture
        foreach ($panier->getLignes() as $lignePanier) {
            $article = $lignePanier->getArticle();
            $taille = $lignePanier->getTaille();
            $quantite = $lignePanier->getQuantite();
            $prixUnitaire = (int) ($lignePanier->getPrixUnitaire() * 100); // Prix stocké en centimes

            $ligneFacture = new LigneFacture();
            $ligneFacture->setArticleDesignation($article->getNom());
            $ligneFacture->setArticleTaille($taille);
            $ligneFacture->setQuantite($quantite);
            $ligneFacture->setPrixUnitaire($prixUnitaire);
            $ligneFacture->calculerPrixTotal();

            $facture->addLigneFacture($ligneFacture);
        }

        $this->em->persist($facture);
        // Le flush se fera lors du commit de la transaction dans la méthode success()

        return $facture;
    }
}
