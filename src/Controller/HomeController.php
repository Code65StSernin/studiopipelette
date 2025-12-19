<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\SousCategorieRepository;
use App\Repository\CarouselRepository;
use App\Repository\OffreRepository;
use App\Service\UserCleanupService;
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
    ): Response {
        // Nettoyer automatiquement les comptes non vérifiés de plus de 7 jours
        $userCleanupService->removeUnverifiedOldAccounts();
        
        // Récupérer les 4 derniers articles actifs pour la section "Bougies tendance"
        $featuredArticles = $articleRepository->findBy(
            ['actif' => true],
            ['id' => 'DESC'],
            4
        );
        
        // Récupérer les sous-catégories avec le nombre d'articles actifs pour chacune
        $sousCategories = $sousCategorieRepository->findAll();
        $sousCategoriesWithCount = [];
        
        foreach ($sousCategories as $sousCategorie) {
            $articleCount = $articleRepository->count([
                'sousCategorie' => $sousCategorie,
                'actif' => true
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
}
