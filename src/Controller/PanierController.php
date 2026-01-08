<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CodeRepository;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FactureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/panier')]
class PanierController extends AbstractController
{
    public function __construct(
        private PanierService $panierService,
        private EntityManagerInterface $em,
        private FactureRepository $factureRepository,
    ) {
    }

    /**
     * Affiche le panier
     */
    #[Route('', name: 'app_panier', methods: ['GET'])]
    public function index(): Response
    {
        $panier = $this->panierService->getPanier();
        $erreursStock = $this->panierService->verifierStock();

        $netBtoB = (float) $panier->getTotalTTC();
        $montantRemiseBtoB = $this->panierService->calculerMontantRemiseBtoB($panier);
        
        $sousTotalBrut = $netBtoB + $montantRemiseBtoB;
        
        $remisePourcentage = $panier->getCodePromoPourcentage() ?? 0.0;
        $montantRemise = $remisePourcentage > 0 ? $netBtoB * ($remisePourcentage / 100) : 0.0;
        $totalApresRemise = max(0, $netBtoB - $montantRemise);

        $btoBDiscountPercentage = $this->panierService->getBtoBDiscountPercentage();

        $response = $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'erreursStock' => $erreursStock,
            'sousTotal' => $sousTotalBrut,
            'montantRemiseBtoB' => $montantRemiseBtoB,
            'remisePourcentage' => $remisePourcentage,
            'montantRemise' => $montantRemise,
            'btoBDiscountPercentage' => $btoBDiscountPercentage,
            'btoBDiscountTotal' => $montantRemiseBtoB,
            'totalApresRemise' => $totalApresRemise,
        ]);

        $this->panierService->addSessionCookie($response);

        return $response;
    }

    /**
     * Ajoute un article au panier
     */
    #[Route('/ajouter/{id}', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouter(int $id, Request $request, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            $this->addFlash('danger', 'Article introuvable');
            return $this->redirectToRoute('app_home');
        }

        $taille = $request->request->get('taille');
        $quantite = (int) $request->request->get('quantite', 1);

        if (!$taille) {
            $this->addFlash('danger', 'Veuillez sélectionner une taille');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        try {
            $this->panierService->ajouterArticle($article, $taille, $quantite);
            $this->addFlash('success', 'Article ajouté au panier avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        $response = $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        
        // Ajouter le cookie de session si nécessaire
        $this->panierService->addSessionCookie($response);

        return $response;
    }

    /**
     * Modifie la quantité d'une ligne du panier
     */
    #[Route('/modifier/{id}', name: 'app_panier_modifier', methods: ['POST'])]
    public function modifier(int $id, Request $request): Response
    {
        $quantite = (int) $request->request->get('quantite', 1);

        try {
            $this->panierService->modifierQuantite($id, $quantite);
            $this->addFlash('success', 'Quantité mise à jour');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_panier');
    }

    /**
     * Incrémente la quantité d'une ligne du panier (AJAX-friendly)
     */
    #[Route('/incrementer/{id}', name: 'app_panier_incrementer', methods: ['POST'])]
    public function incrementer(int $id): Response
    {
        try {
            $panier = $this->panierService->getPanier();
            $ligne = null;
            
            foreach ($panier->getLignes() as $l) {
                if ($l->getId() === $id) {
                    $ligne = $l;
                    break;
                }
            }

            if (!$ligne) {
                throw new \Exception('Ligne introuvable');
            }

            $nouvelleQuantite = $ligne->getQuantite() + 1;
            $this->panierService->modifierQuantite($id, $nouvelleQuantite);
            
            $this->addFlash('success', 'Quantité mise à jour');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_panier');
    }

    /**
     * Décrémente la quantité d'une ligne du panier (AJAX-friendly)
     */
    #[Route('/decrementer/{id}', name: 'app_panier_decrementer', methods: ['POST'])]
    public function decrementer(int $id): Response
    {
        try {
            $panier = $this->panierService->getPanier();
            $ligne = null;
            
            foreach ($panier->getLignes() as $l) {
                if ($l->getId() === $id) {
                    $ligne = $l;
                    break;
                }
            }

            if (!$ligne) {
                throw new \Exception('Ligne introuvable');
            }

            $nouvelleQuantite = $ligne->getQuantite() - 1;
            
            if ($nouvelleQuantite < 1) {
                $this->addFlash('warning', 'Utilisez l\'icône poubelle pour supprimer l\'article');
                return $this->redirectToRoute('app_panier');
            }

            $this->panierService->modifierQuantite($id, $nouvelleQuantite);
            
            $this->addFlash('success', 'Quantité mise à jour');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_panier');
    }

    /**
     * Supprime une ligne du panier
     */
    #[Route('/supprimer/{id}', name: 'app_panier_supprimer', methods: ['POST'])]
    public function supprimer(int $id): Response
    {
        try {
            $this->panierService->supprimerLigne($id);
            $this->addFlash('success', 'Article retiré du panier');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_panier');
    }

    /**
     * Vide complètement le panier
     */
    #[Route('/vider', name: 'app_panier_vider', methods: ['POST'])]
    public function vider(): Response
    {
        try {
            $this->panierService->viderPanier();
            $this->addFlash('success', 'Panier vidé');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        $response = $this->redirectToRoute('app_panier');
        $this->panierService->clearSessionCookie($response);

        return $response;
    }

    #[Route('/code', name: 'app_panier_code', methods: ['POST'])]
    public function appliquerCode(
        Request $request,
        CodeRepository $codeRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {
        $tokenValue = (string) $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('promo_code', $tokenValue))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le formulaire a expiré, merci de réessayer.',
            ], 400);
        }

        $codeSaisi = strtoupper(trim((string) $request->request->get('code')));
        if ($codeSaisi === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Merci de saisir un code.',
            ], 400);
        }

        $code = $codeRepository->findOneBy(['code' => $codeSaisi]);
        if (!$code) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce code promo est invalide.',
            ], 400);
        }

        $now = new \DateTimeImmutable();
        if ($now < $code->getDateDebut()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce code promo n’est pas encore actif.',
            ], 400);
        }

        if ($now > $code->getDateFin()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce code promo est périmé.',
            ], 400);
        }

        $user = $this->getUser();
        if ($code->isUsageUnique()) {
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Ce code promo est réservé aux clients connectés.',
                ], 400);
            }

            if ($user->hasUsedPromoCode($code)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous avez déjà utilisé ce code promo.',
                ], 400);
            }
        }

        if ($code->isPremiereCommandeSeulement()) {
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Ce code promo est réservé à la première commande d\'un client connecté.',
                ], 400);
            }

            $nbFactures = $this->factureRepository->countForUser($user);
            if ($nbFactures > 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Ce code promo est réservé à votre première commande.',
                ], 400);
            }
        }

        $panier = $this->panierService->getPanier();
        if ($panier->isEmpty()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Votre panier est vide.',
            ], 400);
        }

        $panier->setCodePromo($code);
        $panier->setCodePromoPourcentage($code->getPourcentageRemise());
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf(
                'Le code <strong>%s</strong> a été appliqué : %d%% de remise sur vos produits.',
                $code->getCode(),
                $code->getPourcentageRemise()
            ),
        ]);
    }
}

