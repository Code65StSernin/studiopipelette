<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Repository\SousCategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function index(
        ArticleRepository $articleRepository,
        CategorieRepository $categorieRepository,
        SousCategorieRepository $sousCategorieRepository
    ): Response {
        $articles = $articleRepository->findBy(['actif' => true]);
        $categories = $categorieRepository->findAll();
        $sousCategories = $sousCategorieRepository->findAll();

        $response = $this->render('sitemap/sitemap.xml.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'sousCategories' => $sousCategories,
        ]);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}

