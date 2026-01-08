<?php

namespace App\Controller;

use App\Service\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/livraison')]
#[IsGranted('ROLE_USER')]
class LivraisonController extends AbstractController
{
    public function __construct(
        private PanierService $panierService
    ) {
    }

    /**
     * Affiche la page de choix du mode de livraison
     */
    #[Route('/choix', name: 'app_livraison_choix', methods: ['GET'])]
    public function choix(): Response
    {
        $panier = $this->panierService->getPanier();

        // Vérifier que le panier n'est pas vide
        if ($panier->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide');
            return $this->redirectToRoute('app_panier');
        }

        // Vérifier les problèmes de stock
        $erreursStock = $this->panierService->verifierStock();
        if (!empty($erreursStock)) {
            $this->addFlash('danger', 'Veuillez corriger les problèmes de stock avant de continuer');
            return $this->redirectToRoute('app_panier');
        }

        $netBtoB = (float) $panier->getTotalTTC();
        $montantRemiseBtoB = $this->panierService->calculerMontantRemiseBtoB($panier);
        $sousTotalBrut = $netBtoB + $montantRemiseBtoB;

        $remisePourcentage = $panier->getCodePromoPourcentage() ?? 0.0;
        $montantRemise = $remisePourcentage > 0 ? $netBtoB * ($remisePourcentage / 100) : 0.0;
        $totalApresRemise = max(0, $netBtoB - $montantRemise);

        return $this->render('livraison/choix.html.twig', [
            'panier' => $panier,
            'sousTotal' => $sousTotalBrut,
            'montantRemiseBtoB' => $montantRemiseBtoB,
            'remisePourcentage' => $remisePourcentage,
            'montantRemise' => $montantRemise,
            'totalApresRemise' => $totalApresRemise,
        ]);
    }

    /**
     * Valide le choix du mode de livraison
     */
    #[Route('/valider', name: 'app_livraison_valider', methods: ['POST'])]
    public function valider(Request $request): Response
    {
        $modeLivraison = $request->request->get('mode_livraison');
        $pointRelaisId = $request->request->get('point_relais_id');
        $pointRelaisNom = $request->request->get('point_relais_nom');
        $pointRelaisAdresse = $request->request->get('point_relais_adresse');

        // TODO: Sauvegarder le choix de livraison dans la session ou en base
        // Pour l'instant, on stocke dans la session
        $session = $request->getSession();
        
        if ($modeLivraison === 'relais') {
            if (!$pointRelaisId) {
                $this->addFlash('danger', 'Veuillez sélectionner un point relais');
                return $this->redirectToRoute('app_livraison_choix');
            }

            $session->set('livraison', [
                'mode' => 'relais',
                'point_relais_id' => $pointRelaisId,
                'point_relais_nom' => $pointRelaisNom,
                'point_relais_adresse' => $pointRelaisAdresse,
            ]);

            $this->addFlash('success', 'Point relais sélectionné : ' . $pointRelaisNom);
        } elseif ($modeLivraison === 'domicile') {
            $session->set('livraison', [
                'mode' => 'domicile',
            ]);

            $this->addFlash('success', 'Livraison à domicile sélectionnée');
        } else {
            $this->addFlash('danger', 'Mode de livraison invalide');
            return $this->redirectToRoute('app_livraison_choix');
        }

        return $this->redirectToRoute('app_paiement');

    }
}



