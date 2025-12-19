<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Repository\SousCategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogueController extends AbstractController
{
    #[Route('/categorie/{slug}', name: 'app_catalogue_categorie')]
    public function categorie(
        string $slug,
        CategorieRepository $categorieRepository,
        ArticleRepository $articleRepository
    ): Response {
        $categorie = $categorieRepository->findOneBy(['slug' => $slug]);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }
        
        // Récupérer tous les articles actifs de cette catégorie
        $articles = $articleRepository->findBy(
            ['categorie' => $categorie, 'actif' => true],
            ['id' => 'DESC']
        );
        
        return $this->render('catalogue/liste.html.twig', [
            'articles' => $articles,
            'categorie' => $categorie,
            'sousCategorie' => null,
        ]);
    }
    
    #[Route('/sous-categorie/{slug}', name: 'app_catalogue_sous_categorie')]
    public function sousCategorie(
        string $slug,
        SousCategorieRepository $sousCategorieRepository,
        ArticleRepository $articleRepository
    ): Response {
        $sousCategorie = $sousCategorieRepository->findOneBy(['slug' => $slug]);
        
        if (!$sousCategorie) {
            throw $this->createNotFoundException('Sous-catégorie non trouvée');
        }
        
        // Récupérer tous les articles actifs de cette sous-catégorie
        $articles = $articleRepository->findBy(
            ['sousCategorie' => $sousCategorie, 'actif' => true],
            ['id' => 'DESC']
        );
        
        // Récupérer la catégorie parente (en supposant que tous les articles ont la même catégorie)
        $categorie = null;
        if (!empty($articles)) {
            $categorie = $articles[0]->getCategorie();
        }
        
        return $this->render('catalogue/liste.html.twig', [
            'articles' => $articles,
            'categorie' => $categorie,
            'sousCategorie' => $sousCategorie,
        ]);
    }
}

