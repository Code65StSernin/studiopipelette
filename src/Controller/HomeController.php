<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\SousCategorieRepository;
use App\Repository\CarouselRepository;
use App\Repository\OffreRepository;
use App\Repository\ArticleCollectionRepository;
use App\Service\UserCleanupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        UserCleanupService $userCleanupService,
        ArticleRepository $articleRepository,
        SousCategorieRepository $sousCategorieRepository,
        CarouselRepository $carouselRepository,
        OffreRepository $offreRepository,
        ArticleCollectionRepository $collectionRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        // Nettoyer automatiquement les comptes non vérifiés de plus de 7 jours
        $userCleanupService->removeUnverifiedOldAccounts();
        
        // Assigner automatiquement des collections aux articles qui n'en ont pas
        $this->assignCollectionsToArticles($articleRepository, $collectionRepository, $entityManager);
        
        // Récupérer les 4 derniers articles actifs pour la section "Bougies tendance"
        $featuredArticles = $articleRepository->findBy(
            [
                'actif' => true,
                'visibilite' => [Article::VISIBILITY_ONLINE, Article::VISIBILITY_BOTH]
            ],
            ['id' => 'DESC'],
            4
        );
        
        // Récupérer les sous-catégories avec le nombre d'articles actifs pour chacune
        $sousCategories = $sousCategorieRepository->findAll();
        $sousCategoriesWithCount = [];
        
        foreach ($sousCategories as $sousCategorie) {
            $articleCount = $articleRepository->count([
                'sousCategorie' => $sousCategorie,
                'actif' => true,
                'visibilite' => [Article::VISIBILITY_ONLINE, Article::VISIBILITY_BOTH]
            ]);
            
            $sousCategoriesWithCount[] = [
                'sousCategorie' => $sousCategorie,
                'articleCount' => $articleCount
            ];
        }

        // Récupérer les slides actifs du carousel
        $carouselItems = $carouselRepository->findActive();
        // Récupérer les offres actives
        $offres = $offreRepository->findActive();
        
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'featuredArticles' => $featuredArticles,
            'sousCategoriesData' => $sousCategoriesWithCount,
            'carousels' => $carouselItems,
            'offres' => $offres,
        ]);
    }
    
    /**
     * Assigner automatiquement des collections aux articles qui n'en ont pas
     * en fonction de leurs couleurs
     */
    private function assignCollectionsToArticles(
        ArticleRepository $articleRepository,
        ArticleCollectionRepository $collectionRepository,
        EntityManagerInterface $entityManager
    ): void {
        // Récupérer tous les articles qui n'ont pas de collection
        $articles = $articleRepository->findAll();
        $articlesWithoutCollection = [];
        
        foreach ($articles as $article) {
            if ($article->getCollections()->isEmpty()) {
                $articlesWithoutCollection[] = $article;
            }
        }
        
        // Si aucun article sans collection, on ne fait rien
        if (empty($articlesWithoutCollection)) {
            return;
        }
        
        // Récupérer toutes les collections avec leurs couleurs (eager loading)
        $collections = $collectionRepository->createQueryBuilder('c')
            ->leftJoin('c.couleurs', 'couleurs')
            ->addSelect('couleurs')
            ->getQuery()
            ->getResult();
        
        // Pour chaque article sans collection
        foreach ($articlesWithoutCollection as $article) {
            $articleCouleurs = $article->getCouleurs();
            
            // Si l'article n'a pas de couleurs, on passe au suivant
            if ($articleCouleurs->isEmpty()) {
                continue;
            }
            
            // Convertir les couleurs de l'article en tableau d'IDs pour comparaison
            $articleCouleurIds = [];
            foreach ($articleCouleurs as $couleur) {
                $articleCouleurIds[] = $couleur->getId();
            }
            
            // Chercher la première collection qui a au moins une couleur en commun
            $matchingCollection = null;
            foreach ($collections as $collection) {
                $collectionCouleurs = $collection->getCouleurs();
                
                // Vérifier si la collection a au moins une couleur en commun avec l'article
                foreach ($collectionCouleurs as $collectionCouleur) {
                    if (in_array($collectionCouleur->getId(), $articleCouleurIds, true)) {
                        $matchingCollection = $collection;
                        break 2; // Sortir des deux boucles
                    }
                }
            }
            
            // Si on a trouvé une collection correspondante, l'ajouter à l'article
            if ($matchingCollection !== null) {
                $article->addCollection($matchingCollection);
                $entityManager->persist($article);
            }
        }
        
        // Flush tous les changements
        $entityManager->flush();
    }
}
