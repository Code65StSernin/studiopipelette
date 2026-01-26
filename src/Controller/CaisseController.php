<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\LigneVente;
use App\Entity\Vente;
use App\Repository\ArticleRepository;
use App\Repository\CategorieVenteRepository;
use App\Repository\SousCategorieVenteRepository;
use App\Repository\TarifRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        \App\Repository\CategorieRepository $categorieRepository
    ): Response {
        $categorieId = $request->query->get('categorie');
        $sousCategorieId = $request->query->get('sous_categorie');
        $categorieShopId = $request->query->get('categorie_shop');
        
        $currentCategorie = null;
        $currentSousCategorie = null;
        $currentShopCategory = null;
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
            } else {
                // Affichage des catégories de la boutique
                $items = $categorieRepository->findBy([], ['nom' => 'ASC']);
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
            'isSousCategorieView' => $isSousCategorieView,
            'isShopCategoryView' => $isShopCategoryView,
            'isTarifView' => $isTarifView,
            'isArticleView' => $isArticleView,
        ]);
    }

    #[Route('/caisse/client-search', name: 'app_caisse_client_search', methods: ['GET'])]
    public function searchClient(Request $request, UserRepository $userRepository): JsonResponse
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

        foreach ($users as $user) {
            $labelParts = [];
            if (method_exists($user, 'getNom') && $user->getNom()) {
                $labelParts[] = $user->getNom();
            }
            if (method_exists($user, 'getPrenom') && $user->getPrenom()) {
                $labelParts[] = $user->getPrenom();
            }

            $results[] = [
                'value' => $user->getId(),
                'label' => implode(' ', $labelParts),
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/caisse/valider', name: 'app_caisse_valider', methods: ['POST'])]
    public function valider(
        Request $request, 
        UserRepository $userRepository, 
        TarifRepository $tarifRepository, 
        ArticleRepository $articleRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $clientId = $data['client'] ?? null;
        $total = $data['total'] ?? 0;
        $method = $data['method'] ?? 'Inconnu';
        $items = $data['items'] ?? [];

        if (!$clientId) {
            return new JsonResponse(['status' => 'error', 'message' => 'Client manquant'], 400);
        }

        $client = $userRepository->find($clientId);
        if (!$client) {
            return new JsonResponse(['status' => 'error', 'message' => 'Client introuvable'], 404);
        }

        $vente = new Vente();
        $vente->setClient($client);
        $vente->setMontantTotal((string) $total);
        $vente->setModePaiement($method);
        // DateVente is set in constructor

        $entityManager->persist($vente);

        foreach ($items as $itemData) {
            $ligneVente = new LigneVente();
            $ligneVente->setVente($vente);
            $ligneVente->setNom($itemData['name']);
            $ligneVente->setPrixUnitaire((string) $itemData['price']);
            $ligneVente->setQuantite(1); // Assuming 1 per item in the cart array as structured in JS

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

        $entityManager->flush();

        return new JsonResponse(['status' => 'success', 'id' => $vente->getId()]);
    }
}
