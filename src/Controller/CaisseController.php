<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Avoir;
use App\Entity\BonAchat;
use App\Entity\Creneau;
use App\Entity\FondCaisse;
use App\Entity\LigneVente;
use App\Entity\Paiement;
use App\Entity\RemiseBanque;
use App\Entity\Reservation;
use App\Entity\UnavailabilityRule;
use App\Entity\Vente;
use App\Repository\ArticleRepository;
use App\Repository\CategorieVenteRepository;
use App\Repository\SousCategorieVenteRepository;
use App\Repository\TarifRepository;
use App\Repository\UserRepository;
use App\Repository\VenteRepository;
use App\Service\SocieteConfig;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CaisseController extends AbstractController
{
    #[Route('/caisse', name: 'app_caisse')]
    public function index(
        Request $request,
        CategorieVenteRepository $categorieVenteRepository,
        SousCategorieVenteRepository $sousCategorieVenteRepository,
        TarifRepository $tarifRepository,
        ArticleRepository $articleRepository,
        \App\Repository\CategorieRepository $categorieRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$request->getSession()->get('caisse_pin_validated')) {
            return $this->redirectToRoute('app_caisse_login_pin');
        }
        $categorieId = $request->query->get('categorie');
        $sousCategorieId = $request->query->get('sous_categorie');
        $categorieShopId = $request->query->get('categorie_shop');
        $fournisseurId = $request->query->get('fournisseur_id');
        
        // Vérification ouverture de caisse
        $today = new \DateTime('today');
        $fondCaisseRepo = $entityManager->getRepository(FondCaisse::class);
        $fondAujourdhui = $fondCaisseRepo->findOneBy(['date' => $today]);
        
        $isCaisseOpen = $fondAujourdhui !== null && !$fondAujourdhui->isCloture();
        $isCaisseClosed = $fondAujourdhui !== null && $fondAujourdhui->isCloture();
        $montantPrecedent = 0.0;
        
        if (!$isCaisseOpen && !$isCaisseClosed) {
            // On cherche le dernier fond de caisse STRICTEMENT avant aujourd'hui
            // pour récupérer la valeur de clôture de la veille
            $dernierFond = $fondCaisseRepo->createQueryBuilder('f')
                ->where('f.date < :today')
                ->setParameter('today', $today)
                ->orderBy('f.date', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            
            if ($dernierFond) {
                if ($dernierFond->getMontantCloture() !== null) {
                    // Si on a un montant de clôture explicite, on l'utilise
                    $montantPrecedent = $dernierFond->getMontantCloture();
                } else {
                    // Fallback: calcul théorique pour les anciens enregistrements
                    $datePrecedente = $dernierFond->getDate();
                    $montantFondPrecedent = $dernierFond->getMontant();
                    
                    // Ventes espèces du jour précédent
                    $start = \DateTimeImmutable::createFromMutable($datePrecedente)->setTime(0, 0, 0);
                    $end = \DateTimeImmutable::createFromMutable($datePrecedente)->setTime(23, 59, 59);
                    
                    $venteRepo = $entityManager->getRepository(Vente::class);
                    $ventesEspeceLegacy = $venteRepo->createQueryBuilder('v')
                        ->select('SUM(v.montantTotal)')
                        ->where('v.dateVente BETWEEN :start AND :end')
                        ->andWhere('v.modePaiement LIKE :mode')
                        ->setParameter('start', $start)
                        ->setParameter('end', $end)
                        ->setParameter('mode', 'Espece%')
                        ->getQuery()
                        ->getSingleScalarResult();

                    $ventesEspecePaiements = $entityManager->getRepository(Paiement::class)->createQueryBuilder('p')
                        ->select('SUM(p.montant)')
                        ->where('p.datePaiement BETWEEN :start AND :end')
                        ->andWhere('p.methode LIKE :mode')
                        ->setParameter('start', $start)
                        ->setParameter('end', $end)
                        ->setParameter('mode', 'Espece%')
                        ->getQuery()
                        ->getSingleScalarResult();

                    $ventesEspece = (float)($ventesEspeceLegacy ?? 0.0) + (float)($ventesEspecePaiements ?? 0.0);
                    
                    // Remises en banque du jour précédent
                    $remiseRepo = $entityManager->getRepository(RemiseBanque::class);
                    $remises = $remiseRepo->findBy(['date' => $datePrecedente]);
                    $montantRemis = 0.0;
                    foreach ($remises as $r) {
                        $montantRemis += $r->getMontant();
                    }
                    
                    $montantPrecedent = $montantFondPrecedent + (float)$ventesEspece - $montantRemis;
                }
            }
        }

        $currentCategorie = null;
        $currentSousCategorie = null;
        $currentShopCategory = null;
        $currentFournisseur = null;
        $items = [];
        $isSousCategorieView = false;
        $isShopCategoryView = false;
        $isTarifView = false;
        $isArticleView = false;

        if ($categorieId) {
            $currentCategorie = $categorieVenteRepository->find($categorieId);
        }

        if ($currentCategorie && !$currentCategorie->isPrestation()) {
            // Mode VENTE : on utilise directement les catégories/articles de la boutique
            if ($categorieShopId) {
                $currentShopCategory = $categorieRepository->find($categorieShopId);
                if ($currentShopCategory) {
                    $items = $articleRepository->findBy([
                        'categorie' => $currentShopCategory,
                        'actif' => true,
                        'visibilite' => [Article::VISIBILITY_SHOP, Article::VISIBILITY_BOTH]
                    ], ['nom' => 'ASC']);
                    $isArticleView = true;
                }
            } elseif ($fournisseurId) {
                $currentFournisseur = $userRepository->find($fournisseurId);
                if ($currentFournisseur) {
                    $items = $articleRepository->findBy([
                        'fournisseur' => $currentFournisseur,
                        'actif' => true,
                        'visibilite' => [Article::VISIBILITY_SHOP, Article::VISIBILITY_BOTH]
                    ], ['nom' => 'ASC']);
                    $isArticleView = true;
                }
            } else {
                // Affichage des catégories de la boutique ET des clients dépôt-vente
                $categories = $categorieRepository->findBy([], ['nom' => 'ASC']);
                $fournisseurs = $userRepository->findBy(['clientDepotVente' => true], ['nom' => 'ASC']);
                
                // Fusion des deux listes
                $items = array_merge($categories, $fournisseurs);
                $isShopCategoryView = true;
            }
        } elseif ($sousCategorieId) {
            // Mode PRESTATION (détail sous-catégorie)
            $currentSousCategorie = $sousCategorieVenteRepository->find($sousCategorieId);
            if ($currentSousCategorie) {
                $currentCategorie = $currentSousCategorie->getCategorie();
                
                if ($currentCategorie && $currentCategorie->isPrestation()) {
                    $items = $tarifRepository->findBy(['sousCategorieVente' => $currentSousCategorie]);
                    $isTarifView = true;
                }
            }
        } elseif ($categorieId) {
            // Mode PRESTATION (liste sous-catégories)
            if ($currentCategorie) {
                $items = $sousCategorieVenteRepository->findBy(['categorie' => $currentCategorie]);
                $isSousCategorieView = true;
            }
        } else {
            // Accueil Caisse : liste des catégories de vente
            $items = $categorieVenteRepository->findAll();
        }

        return $this->render('caisse/index.html.twig', [
            'items' => $items,
            'currentCategorie' => $currentCategorie,
            'currentSousCategorie' => $currentSousCategorie,
            'currentShopCategory' => $currentShopCategory,
            'currentFournisseur' => $currentFournisseur,
            'isSousCategorieView' => $isSousCategorieView,
            'isShopCategoryView' => $isShopCategoryView,
            'isTarifView' => $isTarifView,
            'isArticleView' => $isArticleView,
            'isCaisseOpen' => $isCaisseOpen,
            'isCaisseClosed' => $isCaisseClosed,
            'montantPrecedent' => $montantPrecedent,
        ]);
    }

    #[Route('/caisse/client-search', name: 'app_caisse_client_search', methods: ['GET'])]
    public function searchClient(Request $request, UserRepository $userRepository, SocieteConfig $societeConfig): JsonResponse
    {
        $term = trim((string) $request->query->get('q', ''));

        if ($term === '') {
            return new JsonResponse([]);
        }

        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.nom LIKE :term OR u.prenom LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults(20);

        $users = $qb->getQuery()->getResult();

        $results = [];
        
        $isFideliteActive = $societeConfig->isFideliteActive();
        $mode = $societeConfig->getFideliteMode();
        $pointsY = $societeConfig->getFidelitePointsY();
        $pointsZ = $societeConfig->getFidelitePointsZ();
        $visitsX = $societeConfig->getFideliteVisitsX();
        $visitsY = $societeConfig->getFideliteVisitsY();

        foreach ($users as $user) {
            $labelParts = [];
            if (method_exists($user, 'getNom') && $user->getNom()) {
                $labelParts[] = $user->getNom();
            }
            if (method_exists($user, 'getPrenom') && $user->getPrenom()) {
                $labelParts[] = $user->getPrenom();
            }

            $cagnotte = method_exists($user, 'getFideliteCagnotte') ? $user->getFideliteCagnotte() : 0.0;
            
            // Calcul de la cagnotte virtuelle (points/visites)
            $virtualCagnotte = 0.0;
            if ($isFideliteActive) {
                if ($mode === 'points' && $pointsY > 0 && $pointsZ > 0) {
                    $pts = $user->getFidelitePoints();
                    $virtualCagnotte = floor($pts / $pointsY) * $pointsZ;
                } elseif ($mode === 'visits' && $visitsX > 0 && $visitsY > 0) {
                    $visits = $user->getFideliteVisits();
                    $virtualCagnotte = floor($visits / $visitsX) * $visitsY;
                }
            }
            
            $totalCagnotte = $cagnotte + $virtualCagnotte;

            $results[] = [
                'value' => $user->getId(),
                'label' => implode(' ', $labelParts),
                'cagnotte' => (float) $totalCagnotte
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/caisse/check-avoir', name: 'app_caisse_check_avoir', methods: ['POST'])]
    public function checkAvoir(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        if (!$code) {
            return new JsonResponse(['success' => false, 'message' => 'Code manquant']);
        }

        $avoir = $entityManager->getRepository(Avoir::class)->findOneBy(['code' => $code]);

        if (!$avoir) {
            return new JsonResponse(['success' => false, 'message' => 'Code avoir invalide']);
        }
        
        if ($avoir->getDateUtilisation() !== null) {
             return new JsonResponse(['success' => false, 'message' => 'Cet avoir a déjà été utilisé le ' . $avoir->getDateUtilisation()->format('d/m/Y')]);
        }
        
        // Vérifier si l'avoir est expiré
        $dateLimite = new \DateTime();
        $dateLimite->modify('-6 months');
        if ($avoir->getDateCreation() < $dateLimite) {
            return new JsonResponse(['success' => false, 'message' => 'Cet avoir est expiré']);
        }

        return new JsonResponse([
            'success' => true,
            'montant' => (float)$avoir->getMontant(),
            'code' => $avoir->getCode()
        ]);
    }

    #[Route('/caisse/check-bon-achat', name: 'app_caisse_check_bon_achat', methods: ['POST'])]
    public function checkBonAchat(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;
        $clientId = $data['client_id'] ?? null;

        if (!$code) {
            return new JsonResponse(['success' => false, 'message' => 'Code manquant']);
        }

        $bon = $entityManager->getRepository(BonAchat::class)->findOneBy(['code' => $code]);

        if (!$bon) {
            return new JsonResponse(['success' => false, 'message' => 'Bon d\'achat introuvable']);
        }
        
        if ($bon->getClient() && $clientId) {
            if ($bon->getClient()->getId() != $clientId) {
                return new JsonResponse(['success' => false, 'message' => 'Ce bon d\'achat appartient à un autre client (' . $bon->getClient()->getNom() . ' ' . $bon->getClient()->getPrenom() . ')']);
            }
        }
        
        if (!$bon->isValide()) {
             if ($bon->getMontantRestant() <= 0) {
                 return new JsonResponse(['success' => false, 'message' => 'Ce bon d\'achat est épuisé (solde 0 €)']);
             }
             return new JsonResponse(['success' => false, 'message' => 'Ce bon d\'achat a expiré le ' . $bon->getDateExpiration()->format('d/m/Y')]);
        }
        
        $clientName = $bon->getClient() ? (strtoupper($bon->getClient()->getNom()) . ' ' . ucfirst($bon->getClient()->getPrenom())) : '';

        return new JsonResponse([
            'success' => true,
            'montant' => (float)$bon->getMontantRestant(),
            'code' => $bon->getCode(),
            'client' => $clientName
        ]);
    }

    #[Route('/caisse/check-scan', name: 'app_caisse_check_scan', methods: ['POST'])]
    public function checkScan(Request $request, EntityManagerInterface $entityManager, ArticleRepository $articleRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = trim($data['code'] ?? '');

        if (!$code) {
            return new JsonResponse(['success' => false, 'message' => 'Code vide']);
        }

        // 1. Check Article (Gencod)
        $article = $articleRepository->findOneBy(['gencod' => $code, 'actif' => true]);
        if ($article) {
            // Determine size and price
            // Default to first available size
            $tailles = $article->getTailles(); // Array from JSON
            $firstAvailable = null;
            
            if (is_array($tailles)) {
                foreach ($tailles as $t) {
                    if (isset($t['stock']) && $t['stock'] > 0) {
                        $firstAvailable = $t;
                        break;
                    }
                }
            }
            
            // If no stock, take the first one anyway (to show out of stock message or let UI handle it)
            if (!$firstAvailable && is_array($tailles) && count($tailles) > 0) {
                $firstAvailable = $tailles[0];
            }

            if ($firstAvailable) {
                return new JsonResponse([
                    'success' => true,
                    'type' => 'article',
                    'data' => [
                        'id' => $article->getId(),
                        'nom' => $article->getNom(),
                        'prix' => (float)($firstAvailable['prix'] ?? 0),
                        'taille' => $firstAvailable['taille'] ?? '',
                        'stock' => (int)($firstAvailable['stock'] ?? 0)
                    ]
                ]);
            }
        }

        // 2. Check Bon d'achat
        $bon = $entityManager->getRepository(BonAchat::class)->findOneBy(['code' => $code]);
        if ($bon && $bon->isValide()) {
             return new JsonResponse([
                'success' => true,
                'type' => 'bon_achat',
                'data' => [
                    'code' => $bon->getCode(),
                    'montant' => $bon->getMontantRestant(),
                    'client' => $bon->getClient() ? ($bon->getClient()->getNom() . ' ' . $bon->getClient()->getPrenom()) : null
                ]
            ]);
        }

        // 3. Check Avoir
        $avoir = $entityManager->getRepository(Avoir::class)->findOneBy(['code' => $code]);
        if ($avoir && !$avoir->getDateUtilisation()) {
             // Check expiry (6 months)
             $dateLimite = new \DateTime();
             $dateLimite->modify('-6 months');
             if ($avoir->getDateCreation() >= $dateLimite) {
                 return new JsonResponse([
                    'success' => true,
                    'type' => 'avoir',
                    'data' => [
                        'code' => $avoir->getCode(),
                        'montant' => (float)$avoir->getMontant()
                    ]
                ]);
             }
        }

        return new JsonResponse(['success' => false, 'message' => 'Code inconnu ou invalide']);
    }

    #[Route('/caisse/valider', name: 'app_caisse_valider', methods: ['POST'])]
    public function valider(
        Request $request, 
        UserRepository $userRepository, 
        TarifRepository $tarifRepository, 
        ArticleRepository $articleRepository,
        EntityManagerInterface $entityManager,
        SocieteConfig $societeConfig
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $clientId = $data['client'] ?? null;
        $total = $data['total'] ?? 0;
        $method = $data['method'] ?? 'Inconnu';
        $reservationId = $data['reservationId'] ?? null;
        $items = $data['items'] ?? [];
        $avoirCode = $data['avoirCode'] ?? null;

        // Vérification si la caisse est fermée
        $today = new \DateTime('today');
        $fondRepo = $entityManager->getRepository(FondCaisse::class);
        $fondAujourdhui = $fondRepo->findOneBy(['date' => $today]);
        
        if ($fondAujourdhui && $fondAujourdhui->isCloture()) {
            return new JsonResponse(['status' => 'error', 'message' => 'La caisse est fermée pour aujourd\'hui. Impossible d\'enregistrer une vente.'], 400);
        }

        if (!$clientId) {
            return new JsonResponse(['status' => 'error', 'message' => 'Client manquant'], 400);
        }

        // Gestion des paiements multiples
        $payments = $data['payments'] ?? [];
        
        // Fallback pour compatibilité (si appel avec ancienne structure)
        if (empty($payments) && isset($data['method'])) {
            $method = $data['method'];
            $avoirCode = $data['avoirCode'] ?? null;
            $montantTotal = (float)$total;
            
            if ($avoirCode) {
                 $avoirEntity = $entityManager->getRepository(Avoir::class)->findOneBy(['code' => $avoirCode]);
                 if ($avoirEntity) {
                     $montantAvoir = (float)$avoirEntity->getMontant();
                     $partAvoir = min($montantAvoir, $montantTotal);
                     $partReste = max(0, $montantTotal - $partAvoir);
                     
                     $payments[] = ['method' => 'Avoir', 'amount' => $partAvoir, 'code' => $avoirCode];
                     if ($partReste > 0) {
                        $payments[] = ['method' => $method, 'amount' => $partReste];
                     } else if ($method === 'Avoir') {
                        // Tout payé par l'avoir
                     }
                 } else {
                     // Cas étrange où le code avoir est fourni mais invalide (déjà géré par le frontend normalement)
                     $payments[] = ['method' => $method, 'amount' => $montantTotal];
                 }
            } else {
                $payments[] = ['method' => $method, 'amount' => $montantTotal];
            }
        }

        // Check for Bon d'Achat Sale restriction
        $hasBonAchatSale = false;
        foreach ($items as $item) {
            if (($item['type'] ?? '') === 'bon_achat_vente') {
                $hasBonAchatSale = true;
                break;
            }
        }

        if ($hasBonAchatSale) {
            foreach ($payments as $p) {
                $m = $p['method'] ?? '';
                if (in_array($m, ['BonAchat', 'Avoir', 'Fidelite'])) {
                     return new JsonResponse(['status' => 'error', 'message' => "Impossible de payer un Bon d'achat avec un Bon d'achat, un Avoir ou des points Fidélité."], 400);
                }
            }
        }

        $client = $userRepository->find($clientId);
        if (!$client) {
            return new JsonResponse(['status' => 'error', 'message' => 'Client introuvable'], 404);
        }

        $vente = new Vente();
        $vente->setClient($client);
        // Le montant total de la vente doit rester le montant des articles (pour le CA)
        $vente->setMontantTotal((string) $total);

        $modePaiementParts = [];
        
        foreach ($payments as $p) {
            $amount = (float)($p['amount'] ?? 0);
            $methodName = $p['method'] ?? 'Inconnu';
            
            if ($amount <= 0) continue;

            if ($methodName === 'Fidelite') {
                // Paiement par cagnotte fidélité
                // Calcul du solde total disponible (Cagnotte stockée + Virtuelle)
                $storedCagnotte = $client->getFideliteCagnotte();
                $virtualCagnotte = 0.0;
                
                if ($societeConfig->isFideliteActive()) {
                     if ($societeConfig->getFideliteMode() === 'points') {
                         $pts = $client->getFidelitePoints();
                         $threshold = $societeConfig->getFidelitePointsY();
                         $gain = $societeConfig->getFidelitePointsZ();
                         if ($threshold > 0 && $gain > 0) {
                             $virtualCagnotte = floor($pts / $threshold) * $gain;
                         }
                     } elseif ($societeConfig->getFideliteMode() === 'visits') {
                         $visits = $client->getFideliteVisits();
                         $threshold = $societeConfig->getFideliteVisitsX();
                         $gain = $societeConfig->getFideliteVisitsY();
                         if ($threshold > 0 && $gain > 0) {
                             $virtualCagnotte = floor($visits / $threshold) * $gain;
                         }
                     }
                }
                
                $totalAvailable = $storedCagnotte + $virtualCagnotte;

                // Tolérance
                if ($amount > $totalAvailable + 0.01) {
                    return new JsonResponse(['status' => 'error', 'message' => "Solde fidélité insuffisant. Solde: " . number_format($totalAvailable, 2) . " €"], 400);
                }
                
                // Déduction
                $remainingToDeduct = $amount;
                
                // 1. Priorité Cagnotte stockée (Cash)
                $deductedFromStored = min($remainingToDeduct, $storedCagnotte);
                $client->setFideliteCagnotte($storedCagnotte - $deductedFromStored);
                $remainingToDeduct -= $deductedFromStored;
                
                // 2. Déduction des points/visites (Virtuel)
                if ($remainingToDeduct > 0.001) {
                    if ($societeConfig->getFideliteMode() === 'points') {
                        $threshold = $societeConfig->getFidelitePointsY();
                        $gain = $societeConfig->getFidelitePointsZ();
                        // Points nécessaires = (Montant / Gain) * Seuil
                        $pointsNeeded = ($remainingToDeduct / $gain) * $threshold;
                        $client->setFidelitePoints(max(0, $client->getFidelitePoints() - $pointsNeeded));
                    } elseif ($societeConfig->getFideliteMode() === 'visits') {
                        $threshold = $societeConfig->getFideliteVisitsX();
                        $gain = $societeConfig->getFideliteVisitsY();
                        $visitsNeeded = ($remainingToDeduct / $gain) * $threshold;
                        $client->setFideliteVisits(max(0, $client->getFideliteVisits() - ceil($visitsNeeded)));
                    }
                }
                
                $entityManager->persist($client);
                
                $modePaiementParts[] = "Fidélité (" . number_format($amount, 2) . "€)";
                
                $paiement = new Paiement();
                $paiement->setVente($vente);
                $paiement->setMontant((string)$amount);
                $paiement->setMethode('Fidelite');
                $paiement->setDatePaiement(new \DateTimeImmutable());
                $entityManager->persist($paiement);
                
                continue; // Skip the default persistent logic since we handled it
            }

            $paiement = new Paiement();
            $paiement->setVente($vente);
            $paiement->setMontant((string)$amount);
            $paiement->setMethode($methodName);
            $paiement->setDatePaiement(new \DateTimeImmutable());
            
            if ($methodName === 'Avoir') {
                $code = $p['code'] ?? null;
                if ($code) {
                    $avoirEntity = $entityManager->getRepository(Avoir::class)->findOneBy(['code' => $code]);
                    if (!$avoirEntity) {
                        return new JsonResponse(['status' => 'error', 'message' => "Code avoir $code invalide"], 400);
                    }
                    
                    $modePaiementParts[] = "Avoir ($code : " . number_format($amount, 2) . "€)";
                    
                    // Consommation de l'avoir
                    $avoirEntity->setDateUtilisation(new \DateTimeImmutable());
                    $entityManager->persist($avoirEntity);
                } else {
                    $modePaiementParts[] = "Avoir (Inconnu : " . number_format($amount, 2) . "€)";
                }
            } elseif ($methodName === 'BonAchat') {
                $code = $p['code'] ?? null;
                if ($code) {
                    $bonEntity = $entityManager->getRepository(BonAchat::class)->findOneBy(['code' => $code]);
                    if (!$bonEntity) {
                        return new JsonResponse(['status' => 'error', 'message' => "Code bon d'achat $code invalide"], 400);
                    }
                    
                    // Vérification du propriétaire
                    if ($bonEntity->getClient() && $bonEntity->getClient()->getId() !== $client->getId()) {
                        return new JsonResponse(['status' => 'error', 'message' => "Le bon d'achat $code appartient à " . $bonEntity->getClient()->getNom() . " " . $bonEntity->getClient()->getPrenom() . ", pas au client actuel."], 400);
                    }
                    
                    // Vérification montant
                    $restant = (float)$bonEntity->getMontantRestant();
                    // On accepte une tolérance minime pour les flottants
                    if ($amount > $restant + 0.01) {
                        return new JsonResponse(['status' => 'error', 'message' => "Montant bon d'achat insuffisant. Solde: $restant €"], 400);
                    }
                    
                    $modePaiementParts[] = "Bon d'Achat ($code : " . number_format($amount, 2) . "€)";
                    
                    // Mise à jour du solde
                    $nouveauSolde = max(0, $restant - $amount);
                    $bonEntity->setMontantRestant((string)$nouveauSolde);
                    $entityManager->persist($bonEntity);
                } else {
                    $modePaiementParts[] = "Bon d'Achat (Inconnu : " . number_format($amount, 2) . "€)";
                }
            } else {
                $modePaiementParts[] = $methodName . " (" . number_format($amount, 2) . "€)";
            }
            
            $entityManager->persist($paiement);
        }

        $vente->setModePaiement(implode(' + ', $modePaiementParts));

        // DateVente is set in constructor

        $entityManager->persist($vente);

        // --- GESTION ACQUISITION FIDELITE ---
        if ($societeConfig->isFideliteActive()) {
            $mode = $societeConfig->getFideliteMode();
            
            // On incrémente toujours les visites
            $client->setFideliteVisits($client->getFideliteVisits() + 1);
            
            if ($mode === 'points') {
                $x = $societeConfig->getFidelitePointsX(); // points par euro
                if ($x) {
                    $pointsGagnes = $total * $x;
                    $client->setFidelitePoints($client->getFidelitePoints() + $pointsGagnes);
                }
            }
            
            // Note: On ne convertit plus automatiquement en cagnotte.
            // Les points s'accumulent et sont déduits lors de l'utilisation (Paiement).
            
            $entityManager->persist($client);
        }
        // -------------------------------------

        $createdBons = [];

        foreach ($items as $itemData) {
            $ligneVente = new LigneVente();
            $ligneVente->setVente($vente);
            $ligneVente->setNom($itemData['name']);
            $ligneVente->setPrixUnitaire((string) $itemData['price']);
            $ligneVente->setQuantite(1); // Assuming 1 per item in the cart array as structured in JS

            if (($itemData['type'] ?? '') === 'bon_achat_vente') {
                 $bonAchat = new BonAchat();
                 $bonAchat->setClient($client);
                 $montant = (float)$itemData['price'];
                 $bonAchat->setMontantInitial((string)$montant);
                 $bonAchat->setMontantRestant((string)$montant);
                 $bonAchat->setDateCreation(new \DateTimeImmutable());
                 $bonAchat->setDateExpiration((new \DateTimeImmutable())->modify('+1 year'));
                 
                 $entityManager->persist($bonAchat);
                 $createdBons[] = $bonAchat;
            }

            if (isset($itemData['id'])) {
                // On ne lie le Tarif que si c'est une prestation (type = tarif ou non défini)
                // Pour les articles boutique (type = article), on ne lie pas de Tarif car l'ID correspond à un Article
                $type = $itemData['type'] ?? 'tarif';
                
                if ($type === 'tarif') {
                    $tarif = $tarifRepository->find($itemData['id']);
                    if ($tarif) {
                        $ligneVente->setTarif($tarif);
                    }
                } elseif ($type === 'article') {
                    $article = $articleRepository->find($itemData['id']);
                    $taille = $itemData['taille'] ?? null;
                    
                    if ($article) {
                        $ligneVente->setArticle($article);
                    }

                    if ($article && $taille) {
                        $isForced = $itemData['isForced'] ?? false;
                        
                        // On vérifie le stock actuel
                        $tailles = $article->getTailles();
                        $stockActuel = 0;
                        if ($tailles) {
                            foreach ($tailles as $t) {
                                if (isset($t['taille']) && $t['taille'] === $taille) {
                                    $stockActuel = $t['stock'] ?? 0;
                                    break;
                                }
                            }
                        }

                        // Si stock > 0 ET ce n'est pas une vente forcée, on décrémente
                        if ($stockActuel > 0 && !$isForced) {
                            try {
                                $article->decrementerStock($taille, 1);
                                $entityManager->persist($article);
                            } catch (\RuntimeException $e) {
                                return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
                            }
                        }
                    }
                }
            }

            $entityManager->persist($ligneVente);
        }

        if ($reservationId) {
            $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
            if ($reservation) {
                $reservation->setStatus(Reservation::STATUS_PAID);
            }
        }

        $entityManager->flush();

        // Update Bon Achat codes with generated IDs
        foreach ($createdBons as $bon) {
             $prefix = '30081260';
             $idPart = str_pad((string)$bon->getId(), 5, '0', STR_PAD_LEFT);
             $bon->setCode($prefix . $idPart);
        }
        if (!empty($createdBons)) {
            $entityManager->flush();
        }

        return new JsonResponse(['status' => 'success', 'id' => $vente->getId()]);
    }

    #[Route('/caisse/fond-caisse', name: 'app_caisse_fond_caisse', methods: ['POST'])]
    public function enregistrerFondCaisse(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $montant = isset($data['montant']) ? (float) $data['montant'] : null;

        if ($montant === null || $montant < 0) {
            return new JsonResponse(['status' => 'error', 'message' => 'Montant invalide'], 400);
        }

        $today = new \DateTime('today');

        $fondRepo = $entityManager->getRepository(FondCaisse::class);
        $fond = $fondRepo->findOneBy(['date' => $today]) ?? new FondCaisse();

        if ($fond->getId() === null) {
            $fond->setDate($today);
            $entityManager->persist($fond);
        }

        $fond->setMontant($montant);
        $fond->setCloture(false);

        $entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/caisse/remise-banque', name: 'app_caisse_remise_banque', methods: ['POST'])]
    public function enregistrerRemiseBanque(Request $request, EntityManagerInterface $entityManager, VenteRepository $venteRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $total = isset($data['total']) ? (float) $data['total'] : null;
        $details = isset($data['details']) && is_array($data['details']) ? json_encode($data['details']) : null;

        if ($total === null || $total <= 0) {
            return new JsonResponse(['status' => 'error', 'message' => 'Montant de remise invalide'], 400);
        }

        $today = new \DateTime('today');

        // Calcul du disponible en espèces (Fond de caisse + Ventes espèces - Remises déjà effectuées)
        $fondRepo = $entityManager->getRepository(FondCaisse::class);
        $fond = $fondRepo->findOneBy(['date' => $today]);
        $montantFond = $fond ? $fond->getMontant() : 0.0;

        $start = \DateTimeImmutable::createFromMutable($today)->setTime(0, 0, 0);
        $end = \DateTimeImmutable::createFromMutable($today)->setTime(23, 59, 59);

        $ventesEspeceLegacy = $venteRepository->createQueryBuilder('v')
            ->select('SUM(v.montantTotal)')
            ->where('v.dateVente BETWEEN :start AND :end')
            ->andWhere('v.modePaiement = :mode')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('mode', 'Espece')
            ->getQuery()
            ->getSingleScalarResult();

        $ventesEspecePaiements = $entityManager->getRepository(Paiement::class)->createQueryBuilder('p')
            ->select('SUM(p.montant)')
            ->where('p.datePaiement BETWEEN :start AND :end')
            ->andWhere('p.methode = :mode')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('mode', 'Espece')
            ->getQuery()
            ->getSingleScalarResult();
            
        $montantVentesEspece = (float)($ventesEspeceLegacy ?? 0.0) + (float)($ventesEspecePaiements ?? 0.0);

        $remiseRepo = $entityManager->getRepository(RemiseBanque::class);
        $remises = $remiseRepo->findBy(['date' => $today]);
        $montantDejaRemis = 0.0;
        foreach ($remises as $r) {
            $montantDejaRemis += $r->getMontant();
        }

        $disponible = $montantFond + $montantVentesEspece - $montantDejaRemis;

        if ($total > $disponible) {
            return new JsonResponse([
                'status' => 'error', 
                'message' => sprintf(
                    'Montant indisponible en caisse. Espèces disponibles : %s € (Fond: %s + Ventes: %s - Déjà remis: %s)',
                    number_format($disponible, 2, ',', ' '),
                    number_format($montantFond, 2, ',', ' '),
                    number_format($montantVentesEspece, 2, ',', ' '),
                    number_format($montantDejaRemis, 2, ',', ' ')
                )
            ], 400);
        }

        $remise = new RemiseBanque();
        $remise->setDate($today);
        $remise->setMontant($total);
        $remise->setDetails($details);

        $entityManager->persist($remise);
        $entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/caisse/cloturer', name: 'app_caisse_cloturer', methods: ['POST'])]
    public function cloturerCaisse(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $montant = isset($data['montant']) ? (float) $data['montant'] : null;

        if ($montant === null || $montant < 0) {
            return new JsonResponse(['status' => 'error', 'message' => 'Montant invalide'], 400);
        }

        $today = new \DateTime('today');

        $fondRepo = $entityManager->getRepository(FondCaisse::class);
        $fond = $fondRepo->findOneBy(['date' => $today]);

        if (!$fond) {
            // Should not happen normally if opened, but create if missing
            $fond = new FondCaisse();
            $fond->setDate($today);
            $fond->setMontant(0.0); // Should have been opened
            $entityManager->persist($fond);
        }

        $fond->setMontantCloture($montant);
        $fond->setCloture(true);

        $entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/caisse/reouvrir', name: 'app_caisse_reouvrir', methods: ['POST'])]
    public function reouvrirCaisse(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $pin = $data['pin'] ?? '';

        // Hardcoded PIN for now as per context memory "1234"
        if ($pin !== '1234') {
             return new JsonResponse(['status' => 'error', 'message' => 'Code PIN incorrect'], 403);
        }

        $today = new \DateTime('today');
        $fondRepo = $entityManager->getRepository(FondCaisse::class);
        $fond = $fondRepo->findOneBy(['date' => $today]);

        if (!$fond) {
             return new JsonResponse(['status' => 'error', 'message' => 'Aucune caisse trouvée pour aujourd\'hui'], 404);
        }

        // We reset the closure status AND the closure amount
        $fond->setCloture(false);
        $fond->setMontantCloture(null);

        $entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/caisse/journaux/x', name: 'app_caisse_x', methods: ['GET'])]
    public function xDeCaisse(
        VenteRepository $venteRepository,
        EntityManagerInterface $entityManager,
        SocieteConfig $societeConfig
    ): Response {
        $today = new \DateTime('today');
        
        $start = \DateTimeImmutable::createFromMutable($today)->setTime(0, 0, 0);
        $end = \DateTimeImmutable::createFromMutable($today)->setTime(23, 59, 59);

        $ventes = $venteRepository->createQueryBuilder('v')
            ->andWhere('v.dateVente BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        $totauxParMoyen = [];
        $detailsParMoyen = [];
        $totalGeneral = 0.0;

        foreach ($ventes as $vente) {
            if (!$vente instanceof Vente) {
                continue;
            }

            // Gestion des Avoirs émis (Vente négative liée à un Avoir)
            if ($vente->getMontantTotal() < 0 && $vente->getAvoir()) {
                $mode = 'Avoir Émis';
                $montant = (float) $vente->getMontantTotal();

                if (!isset($totauxParMoyen[$mode])) {
                    $totauxParMoyen[$mode] = 0.0;
                    $detailsParMoyen[$mode] = [];
                }

                $totauxParMoyen[$mode] += $montant;
                $totalGeneral += $montant;

                foreach ($vente->getLigneVentes() as $ligne) {
                    $detailsParMoyen[$mode][] = [
                        'nom' => $ligne->getNom(),
                        'prix' => $ligne->getPrixUnitaire(),
                        'quantite' => $ligne->getQuantite(),
                        'total' => $ligne->getPrixUnitaire() * $ligne->getQuantite()
                    ];
                }
                continue;
            }

            $paiements = $vente->getPaiements();
            $countPaiements = count($paiements);

            if ($countPaiements > 0) {
                $totalGeneral += (float) $vente->getMontantTotal();
                $hasMultiplePayments = $countPaiements > 1;

                foreach ($paiements as $paiement) {
                    $mode = $paiement->getMethode();
                    $pMontant = (float) $paiement->getMontant();

                    if (!isset($totauxParMoyen[$mode])) {
                        $totauxParMoyen[$mode] = 0.0;
                        $detailsParMoyen[$mode] = [];
                    }

                    $totauxParMoyen[$mode] += $pMontant;

                    if ($hasMultiplePayments) {
                        $detailsParMoyen[$mode][] = [
                            'nom' => 'Vente #' . $vente->getId() . ' (Partiel)',
                            'prix' => $pMontant,
                            'quantite' => 1,
                            'total' => $pMontant
                        ];
                    } else {
                        foreach ($vente->getLigneVentes() as $ligne) {
                            $detailsParMoyen[$mode][] = [
                                'nom' => $ligne->getNom(),
                                'prix' => $ligne->getPrixUnitaire(),
                                'quantite' => $ligne->getQuantite(),
                                'total' => $ligne->getPrixUnitaire() * $ligne->getQuantite()
                            ];
                        }
                    }
                }
            } else {
                $mode = $vente->getModePaiement() ?: 'Inconnu';
                $montant = (float) $vente->getMontantTotal();

                if (!isset($totauxParMoyen[$mode])) {
                    $totauxParMoyen[$mode] = 0.0;
                    $detailsParMoyen[$mode] = [];
                }

                $totauxParMoyen[$mode] += $montant;
                $totalGeneral += $montant;
                
                foreach ($vente->getLigneVentes() as $ligne) {
                    $detailsParMoyen[$mode][] = [
                        'nom' => $ligne->getNom(),
                        'prix' => $ligne->getPrixUnitaire(),
                        'quantite' => $ligne->getQuantite(),
                        'total' => $ligne->getPrixUnitaire() * $ligne->getQuantite()
                    ];
                }
            }
        }

        $fondRepo = $entityManager->getRepository(FondCaisse::class);
        $fond = $fondRepo->findOneBy(['date' => $today]);
        $fondMontant = $fond ? $fond->getMontant() : 0.0;
        $montantCloture = $fond ? $fond->getMontantCloture() : null;

        $remiseRepo = $entityManager->getRepository(RemiseBanque::class);
        $remises = $remiseRepo->findBy(['date' => $today]);
        $totalRemises = 0.0;

        foreach ($remises as $remise) {
            if ($remise instanceof RemiseBanque) {
                $totalRemises += $remise->getMontant();
            }
        }
        
        // Calcul du montant théorique en espèces
        $totalEspeces = $totauxParMoyen['Espece'] ?? 0.0;
        $montantTheoriqueEspeces = $fondMontant + $totalEspeces - $totalRemises;

        ksort($totauxParMoyen);

        $societeNom = $societeConfig->getNom();
        $societeAdresse = $societeConfig->getAdresse();
        $societeCp = $societeConfig->getCodePostal();
        $societeVille = $societeConfig->getVille();
        $societeTelephone = $societeConfig->getTelephone();

        $dateLabel = $today->format('d/m/Y');

        $html = '<html><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            h1 { text-align: center; margin-bottom: 10px; }
            h2 { margin-top: 25px; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #000; padding: 4px 6px; text-align: right; }
            th.label, td.label { text-align: left; }
            .small { font-size: 11px; }
            .societe { text-align: center; margin-bottom: 20px; }
        </style></head><body>';

        $html .= '<div class="societe"><strong>' . htmlspecialchars((string) $societeNom) . '</strong><br>';

        if ($societeAdresse || $societeCp || $societeVille) {
            $html .= htmlspecialchars((string) $societeAdresse) . '<br>' .
                htmlspecialchars((string) $societeCp . ' ' . (string) $societeVille) . '<br>';
        }

        if ($societeTelephone) {
            $html .= 'Tél : ' . htmlspecialchars((string) $societeTelephone);
        }

        $html .= '</div>';

        $html .= '<h1>X de caisse du ' . $dateLabel . '</h1>';

        $html .= '<h2>Fond de caisse</h2>';
        $html .= '<table><tr><th class="label">Libellé</th><th>Montant</th></tr>';
        $html .= '<tr><td class="label">Fond de caisse du jour</td><td>' . number_format($fondMontant, 2, ',', ' ') . ' €</td></tr>';
        $html .= '</table>';

        $html .= '<h2>Ventes par moyen de paiement</h2>';
        $html .= '<table><tr><th class="label">Moyen de paiement</th><th>Montant</th></tr>';

        foreach ($totauxParMoyen as $mode => $montant) {
            $html .= '<tr><td class="label">' . htmlspecialchars($mode) . '</td><td>' . number_format($montant, 2, ',', ' ') . ' €</td></tr>';
        }

        $html .= '<tr><th class="label">Total général ventes</th><th>' . number_format($totalGeneral, 2, ',', ' ') . ' €</th></tr>';
        $html .= '</table>';

        $html .= '<h2>Détail des opérations par moyen de paiement</h2>';
        foreach ($detailsParMoyen as $mode => $lignes) {
            $html .= '<h3>' . htmlspecialchars($mode) . '</h3>';
            $html .= '<table><tr><th class="label">Article / Prestation</th><th>Qté</th><th>Prix Unit.</th><th>Total</th></tr>';
            foreach ($lignes as $ligne) {
                $html .= '<tr>';
                $html .= '<td class="label">' . htmlspecialchars($ligne['nom']) . '</td>';
                $html .= '<td>' . $ligne['quantite'] . '</td>';
                $html .= '<td>' . number_format($ligne['prix'], 2, ',', ' ') . ' €</td>';
                $html .= '<td>' . number_format($ligne['total'], 2, ',', ' ') . ' €</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '<h2>Remises en banque</h2>';
        $html .= '<table><tr><th class="label">Libellé</th><th>Montant</th></tr>';
        $html .= '<tr><td class="label">Total des remises en banque du jour</td><td>' . number_format($totalRemises, 2, ',', ' ') . ' €</td></tr>';
        $html .= '</table>';

        $html .= '<h2>État théorique Caisse Espèces</h2>';
        $html .= '<table><tr><th class="label">Libellé</th><th>Montant</th></tr>';
        $html .= '<tr><td class="label">Montant théorique en caisse (Fond + Ventes Espèces - Remises)</td><td><strong>' . number_format($montantTheoriqueEspeces, 2, ',', ' ') . ' €</strong></td></tr>';
        
        $html .= '</table>';

        $html .= '</body></html>';

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();

        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="x_caisse_' . $today->format('Ymd') . '.pdf"');

        return $response;
    }

    #[Route('/caisse/journaux/z', name: 'app_caisse_z', methods: ['GET'])]
    public function zDeCaisse(
        VenteRepository $venteRepository,
        EntityManagerInterface $entityManager,
        SocieteConfig $societeConfig
    ): Response {
        $today = new \DateTime('today');
        
        $start = \DateTimeImmutable::createFromMutable($today)->setTime(0, 0, 0);
        $end = \DateTimeImmutable::createFromMutable($today)->setTime(23, 59, 59);

        $ventes = $venteRepository->createQueryBuilder('v')
            ->andWhere('v.dateVente BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        $totauxParMoyen = [];
        $detailsParMoyen = [];
        $totalGeneral = 0.0;

        foreach ($ventes as $vente) {
            if (!$vente instanceof Vente) {
                continue;
            }

            // Gestion des Avoirs émis (Vente négative liée à un Avoir)
            if ($vente->getMontantTotal() < 0 && $vente->getAvoir()) {
                $mode = 'Avoir Émis';
                $montant = (float) $vente->getMontantTotal();

                if (!isset($totauxParMoyen[$mode])) {
                    $totauxParMoyen[$mode] = 0.0;
                    $detailsParMoyen[$mode] = [];
                }

                $totauxParMoyen[$mode] += $montant;
                $totalGeneral += $montant;

                foreach ($vente->getLigneVentes() as $ligne) {
                    $detailsParMoyen[$mode][] = [
                        'nom' => $ligne->getNom(),
                        'prix' => $ligne->getPrixUnitaire(),
                        'quantite' => $ligne->getQuantite(),
                        'total' => $ligne->getPrixUnitaire() * $ligne->getQuantite()
                    ];
                }
                continue;
            }

            $paiements = $vente->getPaiements();
            $countPaiements = count($paiements);

            if ($countPaiements > 0) {
                $totalGeneral += (float) $vente->getMontantTotal();
                $hasMultiplePayments = $countPaiements > 1;

                foreach ($paiements as $paiement) {
                    $mode = $paiement->getMethode();
                    $pMontant = (float) $paiement->getMontant();

                    if (!isset($totauxParMoyen[$mode])) {
                        $totauxParMoyen[$mode] = 0.0;
                        $detailsParMoyen[$mode] = [];
                    }

                    $totauxParMoyen[$mode] += $pMontant;

                    if ($hasMultiplePayments) {
                        $detailsParMoyen[$mode][] = [
                            'nom' => 'Vente #' . $vente->getId() . ' (Partiel)',
                            'prix' => $pMontant,
                            'quantite' => 1,
                            'total' => $pMontant
                        ];
                    } else {
                        foreach ($vente->getLigneVentes() as $ligne) {
                            $detailsParMoyen[$mode][] = [
                                'nom' => $ligne->getNom(),
                                'prix' => $ligne->getPrixUnitaire(),
                                'quantite' => $ligne->getQuantite(),
                                'total' => $ligne->getPrixUnitaire() * $ligne->getQuantite()
                            ];
                        }
                    }
                }
            } else {
                $mode = $vente->getModePaiement() ?: 'Inconnu';
                $montant = (float) $vente->getMontantTotal();

                if (!isset($totauxParMoyen[$mode])) {
                    $totauxParMoyen[$mode] = 0.0;
                    $detailsParMoyen[$mode] = [];
                }

                $totauxParMoyen[$mode] += $montant;
                $totalGeneral += $montant;
                
                foreach ($vente->getLigneVentes() as $ligne) {
                    $detailsParMoyen[$mode][] = [
                        'nom' => $ligne->getNom(),
                        'prix' => $ligne->getPrixUnitaire(),
                        'quantite' => $ligne->getQuantite(),
                        'total' => $ligne->getPrixUnitaire() * $ligne->getQuantite()
                    ];
                }
            }
        }

        $fondRepo = $entityManager->getRepository(FondCaisse::class);
        $fond = $fondRepo->findOneBy(['date' => $today]) ?? new FondCaisse();
        $fondMontant = $fond->getMontant();
        $montantCloture = $fond->getMontantCloture();

        // On ne force plus la clôture ici, elle est faite via l'action POST avant l'appel PDF
        // Si le PDF est appelé sans clôture préalable (ex: accès direct URL), on ne clôture PAS.
        // Mais pour l'affichage, on montre les infos.
        
        if ($fond->getId() === null) {
            // Just for display consistency if not exists
             $fond->setMontant(0.0);
        }

        $remiseRepo = $entityManager->getRepository(RemiseBanque::class);
        $remises = $remiseRepo->findBy(['date' => $today]);
        $totalRemises = 0.0;

        foreach ($remises as $remise) {
            if ($remise instanceof RemiseBanque) {
                $totalRemises += $remise->getMontant();
            }
        }
        
        // Calcul du montant théorique en espèces
        $totalEspeces = $totauxParMoyen['Espece'] ?? 0.0;
        $montantTheoriqueEspeces = $fondMontant + $totalEspeces - $totalRemises;

        ksort($totauxParMoyen);

        $societeNom = $societeConfig->getNom();
        $societeAdresse = $societeConfig->getAdresse();
        $societeCp = $societeConfig->getCodePostal();
        $societeVille = $societeConfig->getVille();
        $societeTelephone = $societeConfig->getTelephone();

        $dateLabel = $today->format('d/m/Y');

        $html = '<html><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            h1 { text-align: center; margin-bottom: 10px; }
            h2 { margin-top: 25px; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #000; padding: 4px 6px; text-align: right; }
            th.label, td.label { text-align: left; }
            .small { font-size: 11px; }
            .societe { text-align: center; margin-bottom: 20px; }
        </style></head><body>';

        $html .= '<div class="societe"><strong>' . htmlspecialchars((string) $societeNom) . '</strong><br>';

        if ($societeAdresse || $societeCp || $societeVille) {
            $html .= htmlspecialchars((string) $societeAdresse) . '<br>' .
                htmlspecialchars((string) $societeCp . ' ' . (string) $societeVille) . '<br>';
        }

        if ($societeTelephone) {
            $html .= 'Tél : ' . htmlspecialchars((string) $societeTelephone);
        }

        $html .= '</div>';

        $html .= '<h1>Z de caisse du ' . $dateLabel . '</h1>';

        $html .= '<h2>Fond de caisse</h2>';
        $html .= '<table><tr><th class="label">Libellé</th><th>Montant</th></tr>';
        $html .= '<tr><td class="label">Fond de caisse du jour</td><td>' . number_format($fondMontant, 2, ',', ' ') . ' €</td></tr>';
        $html .= '</table>';

        $html .= '<h2>Ventes par moyen de paiement</h2>';
        $html .= '<table><tr><th class="label">Moyen de paiement</th><th>Montant</th></tr>';

        foreach ($totauxParMoyen as $mode => $montant) {
            $html .= '<tr><td class="label">' . htmlspecialchars($mode) . '</td><td>' . number_format($montant, 2, ',', ' ') . ' €</td></tr>';
        }

        $html .= '<tr><th class="label">Total général ventes</th><th>' . number_format($totalGeneral, 2, ',', ' ') . ' €</th></tr>';
        $html .= '</table>';

        $html .= '<h2>Détail des opérations par moyen de paiement</h2>';
        foreach ($detailsParMoyen as $mode => $lignes) {
            $html .= '<h3>' . htmlspecialchars($mode) . '</h3>';
            $html .= '<table><tr><th class="label">Article / Prestation</th><th>Qté</th><th>Prix Unit.</th><th>Total</th></tr>';
            foreach ($lignes as $ligne) {
                $html .= '<tr>';
                $html .= '<td class="label">' . htmlspecialchars($ligne['nom']) . '</td>';
                $html .= '<td>' . $ligne['quantite'] . '</td>';
                $html .= '<td>' . number_format($ligne['prix'], 2, ',', ' ') . ' €</td>';
                $html .= '<td>' . number_format($ligne['total'], 2, ',', ' ') . ' €</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '<h2>Remises en banque</h2>';
        $html .= '<table><tr><th class="label">Libellé</th><th>Montant</th></tr>';
        $html .= '<tr><td class="label">Total des remises en banque du jour</td><td>' . number_format($totalRemises, 2, ',', ' ') . ' €</td></tr>';
        $html .= '</table>';

        $html .= '<h2>État théorique Caisse Espèces</h2>';
        $html .= '<table><tr><th class="label">Libellé</th><th>Montant</th></tr>';
        $html .= '<tr><td class="label">Montant théorique en caisse (Fond + Ventes Espèces - Remises)</td><td><strong>' . number_format($montantTheoriqueEspeces, 2, ',', ' ') . ' €</strong></td></tr>';
        
        if ($montantCloture !== null) {
            $ecart = $montantCloture - $montantTheoriqueEspeces;
            $styleEcart = $ecart < 0 ? 'color: red;' : ($ecart > 0 ? 'color: green;' : '');
            
            $html .= '<tr><td class="label">Montant réel déclaré à la clôture</td><td><strong>' . number_format($montantCloture, 2, ',', ' ') . ' €</strong></td></tr>';
            $html .= '<tr><td class="label">Écart de caisse</td><td style="' . $styleEcart . '"><strong>' . number_format($ecart, 2, ',', ' ') . ' €</strong></td></tr>';
        }

        $html .= '</table>';

        $html .= '</body></html>';

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();

        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="z_caisse_' . $today->format('Ymd') . '.pdf"');

        return $response;
    }

    #[Route('/caisse/reservation/{id}/missed', name: 'app_caisse_reservation_missed', methods: ['POST'])]
    public function markReservationMissed(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            return new JsonResponse(['status' => 'error', 'message' => 'Réservation non trouvée'], 404);
        }

        $reservation->setStatus(Reservation::STATUS_MISSED);
        $entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/caisse/planning', name: 'app_caisse_planning', methods: ['GET'])]
    public function planning(): Response
    {
        return $this->render('caisse/planning.html.twig');
    }

    #[Route('/caisse/api/creneaux', name: 'app_caisse_api_creneaux')]
    public function apiCreneaux(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Accept start/end query params from FullCalendar to limit expansion window
        $startParam = $request->query->get('start');
        $endParam = $request->query->get('end');
        $rangeStart = $startParam ? new \DateTimeImmutable($startParam) : (new \DateTimeImmutable())->sub(new \DateInterval('P7D'));
        $rangeEnd = $endParam ? new \DateTimeImmutable($endParam) : (new \DateTimeImmutable())->add(new \DateInterval('P30D'));

        $events = [];

        // Creneaux (slots)
        $repo = $em->getRepository(Creneau::class);
        $qb = $repo->createQueryBuilder('c')
            ->andWhere('c.date BETWEEN :s AND :e')
            ->setParameter('s', $rangeStart->format('Y-m-d'))
            ->setParameter('e', $rangeEnd->format('Y-m-d'))
            ->orderBy('c.date', 'ASC');

        $items = $qb->getQuery()->getResult();
        foreach ($items as $c) {
            /** @var Creneau $c */
            $start = $c->getDate()->format('Y-m-d').'T'.$c->getStartTime()->format('H:i:s');
            $end = $c->getDate()->format('Y-m-d').'T'.$c->getEndTime()->format('H:i:s');
            $events[] = [
                'id' => 'slot-'.$c->getId(),
                'title' => $c->isBlocked() ? 'Bloqué' : 'Libre',
                'start' => $start,
                'end' => $end,
                'extendedProps' => ['slotKey' => $c->getSlotKey(), 'capacity' => $c->getCapacity(), 'type' => 'slot'],
                'color' => $c->isBlocked() ? '#d3573b' : '#a16877', // Orange (blocked) / Parme (free)
            ];
        }

        // Reservations
        $rRepo = $em->getRepository(Reservation::class);
        $rQb = $rRepo->createQueryBuilder('r')
            ->andWhere('r.dateStart < :e AND r.dateEnd > :s')
            ->setParameter('s', $rangeStart)
            ->setParameter('e', $rangeEnd)
            ->orderBy('r.dateStart', 'ASC');
        $reservations = $rQb->getQuery()->getResult();
        foreach ($reservations as $res) {
            /** @var Reservation $res */
            $start = $res->getDateStart()->format('Y-m-d\TH:i:s');
            $end = $res->getDateEnd()->format('Y-m-d\TH:i:s');
            $prestationsLabels = [];
            $prestationsIds = [];
            foreach ($res->getPrestations() as $t) {
                $prestationsLabels[] = $t->getNom();
                $prestationsIds[] = $t->getId();
            }
            $title = $res->getDateStart()->format('H:i') . ' - ' . $res->getDateEnd()->format('H:i') . ' · ' . ($res->getClientName() ?? 'Réservation');
            
            // Determine color based on status
            $color = '#d4807e'; // Default confirmed
            if ($res->getStatus() === Reservation::STATUS_MISSED) {
                $color = '#fd7e14'; // Orange
            } elseif ($res->getStatus() === Reservation::STATUS_PAID) {
                $color = '#28a745'; // Green
            }

            $events[] = [
                'id' => 'res-'.$res->getId(),
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'extendedProps' => [
                    'reservationId' => $res->getId(),
                    'type' => 'reservation',
                    'clientName' => $res->getClientName(),
                    'clientEmail' => $res->getClientEmail(),
                    'prestations' => $prestationsIds,
                    'prestationsLabel' => implode(', ', $prestationsLabels),
                    'status' => $res->getStatus() ?? Reservation::STATUS_CONFIRMED,
                ],
                'color' => $color,
            ];
        }

        // Unavailability rules -> expand into events in the requested window
        $uRepo = $em->getRepository(UnavailabilityRule::class);
        $rules = $uRepo->findBy(['active' => true]);
        foreach ($rules as $rule) {
            $rec = $rule->getRecurrence();
            if (!$rec) continue;
            $parsed = json_decode($rec, true);
            if (!$parsed) continue;

            $type = $parsed['type'] ?? 'once';
            $ruleStart = isset($parsed['startDate']) ? new \DateTimeImmutable($parsed['startDate']) : null;
            $ruleEnd = isset($parsed['endDate']) ? new \DateTimeImmutable($parsed['endDate']) : null;
            $days = $parsed['daysOfWeek'] ?? null; // array of ints (0=Sun..6=Sat or 1..6)
            $ts = $parsed['timeStart'] ?? null; // 'HH:MM'
            $te = $parsed['timeEnd'] ?? null;

            // Fallback to entity times if missing in JSON
            if (!$ts && $rule->getTimeStart()) {
                $ts = $rule->getTimeStart()->format('H:i');
            }
            if (!$te && $rule->getTimeEnd()) {
                $te = $rule->getTimeEnd()->format('H:i');
            }

            // iterate days in window
            $period = new \DatePeriod($rangeStart, new \DateInterval('P1D'), $rangeEnd->add(new \DateInterval('P1D')));
            foreach ($period as $dt) {
                // respect rule start/end bounds
                if ($ruleStart && $dt < $ruleStart) continue;
                if ($ruleEnd && $dt > $ruleEnd) continue;

                $matches = false;
                if ($type === 'once') {
                    if ($ruleStart && $dt->format('Y-m-d') === $ruleStart->format('Y-m-d')) $matches = true;
                } elseif ($type === 'daily') {
                    $matches = true;
                } elseif ($type === 'weekly') {
                    if ($days && is_array($days)) {
                        // compare using PHP 'w' (0=Sun..6=Sat)
                        $w = (int)$dt->format('w');
                        if (in_array($w, $days, true) || in_array((string)$w, $days, true)) $matches = true;
                        // also support 1..7 mapping where Monday=1
                        if (!$matches) {
                            $n = (int)$dt->format('N'); // 1=Mon..7=Sun
                            $mapped = $n === 7 ? 0 : $n; // convert 7->0 for sunday mapping
                            if (in_array($mapped, $days, true)) $matches = true;
                        }
                    }
                } else {
                    // fallback: no-op (could add monthly/yearly later)
                }

                if ($matches) {
                    if ($ts && $te) {
                        // Clamp to view bounds (08:00 - 20:00) to ensure visibility in this specific calendar
                        $viewStartStr = '08:00';
                        $viewEndStr = '20:00';

                        // Check for overlap
                        if ($ts < $viewEndStr && $te > $viewStartStr) {
                            $rStart = $ts < $viewStartStr ? $viewStartStr : $ts;
                            $rEnd = $te > $viewEndStr ? $viewEndStr : $te;

                            $start = $dt->format('Y-m-d').'T'.$rStart.':00';
                            $end = $dt->format('Y-m-d').'T'.$rEnd.':00';
                            $events[] = [
                                'id' => 'rule-'.$rule->getId().'-'.$dt->format('Ymd'),
                                'title' => '',
                                'start' => $start,
                                'end' => $end,
                                'extendedProps' => ['ruleId' => $rule->getId(), 'type' => 'rule', 'description' => $rule->getName()],
                                'color' => '#808080', // Medium Gray
                            ];
                        }
                    } else {
                        // all-day -> convert to full visible day (08:00 - 20:00)
                        $start = $dt->format('Y-m-d').'T08:00:00';
                        $end = $dt->format('Y-m-d').'T20:00:00';
                        $events[] = [
                            'id' => 'rule-'.$rule->getId().'-'.$dt->format('Ymd'),
                            'title' => '',
                            'start' => $start,
                            'end' => $end,
                            'extendedProps' => ['ruleId' => $rule->getId(), 'type' => 'rule', 'description' => $rule->getName()],
                            'color' => '#808080', // Medium Gray
                        ];
                    }
                }
            }
        }

        return new JsonResponse($events);
    }

    #[Route('/caisse/client-by-email', name: 'app_caisse_client_by_email', methods: ['GET'])]
    public function clientByEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $email = trim((string) $request->query->get('email', ''));
        if ($email === '') {
            return new JsonResponse(['status' => 'error', 'message' => 'Email manquant'], 400);
        }
        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['status' => 'not_found']);
        }
        return new JsonResponse([
            'status' => 'success',
            'value' => $user->getId(),
            'label' => (string) $user,
        ]);
    }

    #[Route('/caisse/tarifs-details', name: 'app_caisse_tarifs_details', methods: ['GET'])]
    public function tarifsDetails(Request $request, TarifRepository $tarifRepository): JsonResponse
    {
        $idsRaw = (string) $request->query->get('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsRaw)));
        if (empty($ids)) {
            return new JsonResponse([]);
        }
        $tarifs = $tarifRepository->findBy(['id' => $ids]);
        $out = [];
        foreach ($tarifs as $t) {
            $out[] = [
                'id' => $t->getId(),
                'name' => $t->getNom(),
                'price' => (float) $t->getTarif(),
                'type' => 'tarif',
            ];
        }
        return new JsonResponse($out);
    }

    #[Route('/caisse/client-by-id', name: 'app_caisse_client_by_id', methods: ['GET'])]
    public function clientById(Request $request, UserRepository $userRepository): JsonResponse
    {
        $id = (int) $request->query->get('id', 0);
        if ($id <= 0) {
            return new JsonResponse(['status' => 'error', 'message' => 'ID manquant'], 400);
        }
        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['status' => 'not_found']);
        }
        return new JsonResponse([
            'status' => 'success',
            'value' => $user->getId(),
            'label' => (string) $user,
        ]);
    }
}
